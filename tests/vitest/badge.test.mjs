import { describe, it, expect } from 'vitest';
import { formatBadge } from './badge.mjs';

describe('formatBadge', () => {
  it('hides badge for zero unread', () => {
    expect(formatBadge(0)).toEqual({ text: '0', hidden: true });
  });

  it('shows N for 1..99 unread', () => {
    expect(formatBadge(1)).toEqual({ text: '1', hidden: false });
    expect(formatBadge(7)).toEqual({ text: '7', hidden: false });
    expect(formatBadge(42)).toEqual({ text: '42', hidden: false });
    expect(formatBadge(99)).toEqual({ text: '99', hidden: false });
  });

  it("clamps to '99+' for >99 unread", () => {
    expect(formatBadge(100)).toEqual({ text: '99+', hidden: false });
    expect(formatBadge(500)).toEqual({ text: '99+', hidden: false });
    expect(formatBadge(99999)).toEqual({ text: '99+', hidden: false });
  });

  it('treats negative counts as zero', () => {
    expect(formatBadge(-1)).toEqual({ text: '0', hidden: true });
    expect(formatBadge(-100)).toEqual({ text: '0', hidden: true });
  });

  it('treats non-numeric counts as zero', () => {
    expect(formatBadge(NaN)).toEqual({ text: '0', hidden: true });
    expect(formatBadge(Infinity)).toEqual({ text: '0', hidden: true });
    expect(formatBadge(undefined)).toEqual({ text: '0', hidden: true });
    expect(formatBadge(null)).toEqual({ text: '0', hidden: true });
    expect(formatBadge('17')).toEqual({ text: '0', hidden: true });
  });

  it('floors fractional inputs', () => {
    expect(formatBadge(3.7)).toEqual({ text: '3', hidden: false });
    expect(formatBadge(99.9)).toEqual({ text: '99', hidden: false });
    expect(formatBadge(100.1)).toEqual({ text: '99+', hidden: false });
  });
});
