export enum OrderStatus {
    PENDING = "pending",
    PROCESSING = "processing",
    SHIPPED = "shipped",
    DELIVERED = "delivered",
    CANCELLED = "cancelled",
}

type TerminalOrderStatus = Exclude<OrderStatus, 'PENDING' | 'PROCESSING' | 'SHIPPED'>;

export interface OrderLineItem {
    itemSku: string;
    name: string;
    price: number;
    quantity: number;
}

export interface OrderDBRecord {
    id: string;
    customerId: string;
    totalPrice: number;
    items: OrderLineItem[];
    status: OrderStatus;
    shippedDate?: Date;
    finalizedDate?: Date;
}

export interface TerminalOrder extends OrderDBRecord {
    status: TerminalOrderStatus;
    finalizedDate: Date;
}

export interface OrderRepository {
    getOrderById(orderId: string): Promise<OrderDBRecord | null>;
    putOrder(order: OrderDBRecord): Promise<void>;
}

type HttpResult =
    | { result: 'ok' }
    | { result: '4XXerror'; error: string }
    | { result: '5XXerror'; error: string };


export class OrderController {
    private orderRepository: OrderRepository;

    constructor(orderRepository: OrderRepository) {
        this.orderRepository = orderRepository;
    }

    getOrder = (orderId: string): Promise<OrderDBRecord | null> => {
        return this.orderRepository.getOrderById(orderId);
    }

    cancelOrder = async (orderId: string): Promise<HttpResult> => {
        const order = await this.orderRepository.getOrderById(orderId);
        if (!order) {
            return { result: '4XXerror', error: 'Order not found' };
        }
        if (!([OrderStatus.PENDING, OrderStatus.PROCESSING, OrderStatus.SHIPPED].includes(order.status))) {
            return { result: '4XXerror', error: `Tried to cancel order ${orderId} but status was ${order.status}` };
        }
        order.status = OrderStatus.CANCELLED;
        order.finalizedDate = new Date();
        await this.orderRepository.putOrder(order);
        return { result: 'ok' };
    }

    shipOrder = async (orderId: string): Promise<HttpResult> => {
        const order = await this.orderRepository.getOrderById(orderId);
        if (!order) {
            return { result: '4XXerror', error: 'Order not found' };
        }
        if (order.status !== OrderStatus.PROCESSING) {
            return { result: '4XXerror', error: `Tried to ship order ${orderId} but status was ${order.status}` };
        }
        order.status = OrderStatus.SHIPPED;
        order.shippedDate = new Date();
        await this.orderRepository.putOrder(order);
        return { result: 'ok' };
    }
}