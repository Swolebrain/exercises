import { describe,  test } from 'vitest';
import * as fc from 'fast-check';

import { OrderController, OrderDBRecord, OrderLineItem, OrderStatus } from './orders';
import { OrderRepository } from './order.repository';
import { IMemoryDb, newDb } from 'pg-mem';


class OrderRepositoryMock implements OrderRepository {
    private db: IMemoryDb;
    constructor(db: IMemoryDb) {
        this.db = db;
        this.db.public.none(`CREATE TABLE orders (
            id varchar(64) PRIMARY KEY,
            customer_id varchar(64),
            total_price integer,
            items jsonb,
            status text,
            sequence_number integer
            );`
        );
    }

    async getOrderById(orderId: string): Promise<OrderDBRecord | null> {
        const dbResult = await this.db.public.many(`SELECT * FROM orders WHERE id = '${orderId}'`);
        if (dbResult.length === 0) {
            return null;
        }
        return {
            id: dbResult[0].id,
            customerId: dbResult[0].customer_id,
            totalPrice: dbResult[0].total_price,
            items: dbResult[0].items as OrderLineItem[],
            status: dbResult[0].status,
        } satisfies OrderDBRecord;
    }
    async putOrder(order: OrderDBRecord): Promise<void> {
        await this.db.public.none(
            `INSERT INTO orders (id, customer_id, total_price, items, status) 
            VALUES ('${order.id}', '${order.customerId}', ${order.totalPrice}, '${JSON.stringify(order.items)}', '${order.status}');`
        );
    }
}

describe('OrderController race condition property test', () => {
    let orderRepo: OrderRepositoryMock;
    beforeEach(() => {
        orderRepo = new OrderRepositoryMock(newDb());
    });

    test('should not be able to cancel an order that is not pending', async () => {
        await fc.assert(
            fc.asyncProperty(
                fc.scheduler(),
                async (s) => {
                    const orderController = new OrderController(orderRepo);
                    // seed the order, already in processing status
                    await orderRepo.putOrder({
                        id: '1',
                        customerId: 'c1',
                        totalPrice: 100,
                        items: [{ itemSku: 'i1', name: 'Item 1', price: 100, quantity: 1 }],
                        status: OrderStatus.PROCESSING
                    });
                    console.log('order seeded');

                    // schedule shipping and cancelling right away, letting fast-check handle interleaving
                    let successCount = 0;
                    const shipResult = s.schedule(orderController.shipOrder('1'))
                        .then(sr => {
                            if (sr.result === 'ok') successCount++;
                        })
                    const cancelResult = s.schedule(orderController.cancelOrder('1'))
                        .then(cr => {
                            if (cr.result === 'ok') successCount++;
                        });
                    await s.waitIdle();

                    const order = await orderController.getOrder('1');

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