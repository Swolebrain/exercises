import { describe,  test } from 'vitest';
import * as fc from 'fast-check';

import { displayShoppingCart, LineItem } from './displayShoppingCart';

const lineItemArb = fc.record<LineItem>({
    sku: fc.integer({min: 1, max: 20}),
    name: fc.string(),
    price: fc.float(),
    quantity: fc.integer({min: 1, max: 10}),
});

describe('displayShoppingCart function proptests', () => {
    test('returned list of line items should have length less than or equal to the input list', () => {
        
    });
});