import { OrderDBRecord } from "./orders";

export interface OrderRepository {
    getOrderById(orderId: string): Promise<OrderDBRecord | null>;
    updateOrder(order: OrderDBRecord): Promise<void>;
}
