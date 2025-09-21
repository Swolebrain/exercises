import { describe,  test } from 'vitest';
import * as fc from 'fast-check';

import { OrderController, OrderDBRecord, OrderLineItem, OrderStatus } from './orders';
import { OrderRepository } from './order.repository';
import Database from 'better-sqlite3';


class OrderRepositoryMock implements OrderRepository {
    private db: Database.Database;
    constructor(db: Database.Database) {
        this.db = db;
        this.db.exec(`CREATE TABLE orders (
            id varchar(64) PRIMARY KEY,
            customerId varchar(64) not null,
            totalPrice integer not null,
            items jsonb not null,
            status text not null,
            shippedDate text,
            finalizedDate text
            );`
        );
    }

    async getOrderById(orderId: string): Promise<OrderDBRecord | null> {
        const stmt = this.db.prepare(`SELECT * FROM orders WHERE id = ?`);
        const dbResult = stmt.get(orderId);
        if (!dbResult) {
            return null;
        }
        return dbResult as OrderDBRecord;
    }
    async updateOrder(order: OrderDBRecord): Promise<void> {
        const stmt = this.db.prepare(`UPDATE orders 
            SET customerId = ?, totalPrice = ?, items = ?, status = ?, shippedDate = ?, finalizedDate = ?
            WHERE id = ?`);
        stmt.run(
            order.id,
            order.customerId,
            order.totalPrice,
            JSON.stringify(order.items),
            order.status,
            order.shippedDate || null,
            order.finalizedDate || null
        );
    }
}

describe('OrderController race condition property test', () => {
    let orderRepo: OrderRepositoryMock;
    let db: Database.Database;
    let orderController: OrderController;
    beforeEach(() => {
        db = new Database(':memory:');
        orderRepo = new OrderRepositoryMock(db);
        orderController = new OrderController(orderRepo);
    });
    afterEach(() => {
        db.close();
    });

    test('should not be able to cancel an order that is not pending', async () => {
        // seed the order, already in processing status
        await Promise.all(
            Array(100).fill('').map((_, idx) => {
                db.prepare(`INSERT INTO orders (id, customerId, totalPrice, items, status) VALUES (?, ?, ?, ?, ?)`).run(
                    `${idx+1}`,
                    'c1',
                    100,
                    JSON.stringify([{ itemSku: 'i1', name: 'Item 1', price: 100, quantity: 1 }]),
                    OrderStatus.PROCESSING
                );
            })
        );
        let currentOrderId = 0;
        console.log('order seeded');
        await fc.assert(
            fc.asyncProperty(
                fc.scheduler(),
                async (s) => {
                    currentOrderId++;
                    // schedule shipping and cancelling right away, letting fast-check handle interleaving
                    let successCount = 0;
                    const shipResult = s.schedule(orderController.shipOrder(`${currentOrderId}`))
                        .then(sr => {
                            if (sr.result === 'ok') successCount++;
                        })
                    const cancelResult = s.schedule(orderController.cancelOrder(`${currentOrderId}`))
                        .then(cr => {
                            if (cr.result === 'ok') successCount++;
                        });
                    await s.waitIdle();

                    const order = await orderController.getOrder(`${currentOrderId}`);

                    // Two approaches to check for race conditions:
                    // 1: check that only one of the two operations succeeded
                    expect(successCount).toBe(1);

                    // 2: check data integrity
                    if (order?.status === OrderStatus.SHIPPED) {
                        expect(order.finalizedDate).not.toBeDefined();
                    } else if (order?.status === OrderStatus.CANCELLED) {
                        expect(order.shippedDate).not.toBeDefined();
                    } else {
                        throw new Error(`Order status was ${order?.status}`);
                    }
                    
                    
                }
            )
        );
    });
});