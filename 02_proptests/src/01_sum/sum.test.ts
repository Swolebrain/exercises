import { describe, it, expect, test } from 'vitest';
import * as fc from 'fast-check';
import { sum } from './sum';

describe('sum function unit tests', () => {
    test('adds 1 + 2 to equal 3', () => {
        console.log('adds 1 + 2 to equal 3');
        expect(sum(1, 2)).toBe(3);
    });
});

/**
 * Syntax:
fc.property(
    ...arbitraries // how to generate the values received as inputs of the predicate
    predicate // how to check if the code worked
  );
*/

describe('sum function proptests', () => {
    test('should return a value greater than either argument for positive numbers', () => {
        fc.assert(
            fc.property(
                fc.double({ min: 0, max: 1000 }),
                fc.double({ min: 0, max: 1000 }),
                (a, b) => {
                    const result = sum(a, b);
                    return result > a && result > b;
                }
            )
        );
    });

    test('should return a value greater than either argument for mixed positive numbers', () => {
        fc.assert(
            fc.property(
                fc.double({ min: 0, max: 1000 }),
                fc.double({ min: 0, max: 1000 }),
                (a, b) => {
                    const result = sum(a, b);
                    return result >= Math.max(a, b);
                }
            )
        );
    });
});
