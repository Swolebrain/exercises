import { describe,  test } from 'vitest';
import * as fc from 'fast-check';

import { OrderController, OrderRepository, OrderDBRecord, OrderStatus } from './orders';

function wait(ms?: number) {
    const waitMS = ms ?? 100; //Math.ceil(Math.random() * 80) + 20;
    return new Promise(resolve => setTimeout(resolve, waitMS));
}

class OrderRepositoryMock implements OrderRepository {
    public orders: Map<string, OrderDBRecord> = new Map(); // public for testing purposes

    async getOrderById(orderId: string): Promise<OrderDBRecord | null> {
        await wait(); // simulate db latency
        if (this.orders.has(orderId)) {
            return this.orders.get(orderId)!;
        }
        return null;
    }
    async putOrder(order: OrderDBRecord): Promise<void> {
        await wait(); //simulate db latency
        this.orders.set(order.id, order);
    }
}

describe('OrderController race condition property test', () => {
    let db: OrderRepositoryMock;
    beforeEach(() => {
        db = new OrderRepositoryMock();
    });

    test('should not be able to cancel an order that is not pending', async () => {
        await fc.assert(
            fc.asyncProperty(
                fc.scheduler(),
                async (s) => {
                    const orderController = new OrderController(db);
                    // seed the order, already in processing status
                    db.orders.set('1', {
                        id: '1',
                        customerId: 'c1',
                        totalPrice: 100,
                        items: [
                            {
                                itemSku: 'i1',
                                name: 'Item 1',
                                price: 100,
                                quantity: 1
                            },
                        ],
                        status: OrderStatus.PROCESSING
                    });

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