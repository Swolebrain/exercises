import { OrderDBRecord } from "./orders";

export interface OrderRepository {
    getOrderById(orderId: string): Promise<OrderDBRecord | null>;
    putOrder(order: OrderDBRecord): Promise<void>;
}
