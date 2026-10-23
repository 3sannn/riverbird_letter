<?php

declare(strict_types=1);

namespace App\Invoices;

final class InvoiceCalculator
{
    /**
     * @param array<int, array{quantity: string|float, unit_price: string|float}> $items
     * @return array{subtotal: string, tax_amount: string, total: string, items: array<int, array{line_total: string}>}
     */
    public function calculate(
        array $items,
        string $discountAmount = '0.00',
        string $taxRate = '0.00',
        string $shippingAmount = '0.00'
    ): array {
        $subtotal = '0.00';
        $computedItems = [];

        foreach ($items as $index => $item) {
            $qty = $this->normalizeAmount((string) ($item['quantity'] ?? '1'));
            $price = $this->normalizeAmount((string) ($item['unit_price'] ?? '0'));
            $lineTotal = bcmul($qty, $price, 2);
            $subtotal = bcadd($subtotal, $lineTotal, 2);
            $computedItems[$index] = ['line_total' => $lineTotal];
        }

        $discount = $this->normalizeAmount($discountAmount);
        if (bccomp($discount, $subtotal, 2) === 1) {
            throw new \InvalidArgumentException('Discount cannot exceed subtotal.');
        }

        $afterDiscount = bcsub($subtotal, $discount, 2);
        $taxRateNorm = $this->normalizeAmount($taxRate);
        $taxAmount = '0.00';
        if (bccomp($taxRateNorm, '0', 2) === 1) {
            $taxAmount = bcdiv(bcmul($afterDiscount, $taxRateNorm, 4), '100', 2);
        }

        $shipping = $this->normalizeAmount($shippingAmount);
        $total = bcadd(bcadd($afterDiscount, $taxAmount, 2), $shipping, 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'items' => $computedItems,
        ];
    }

    public function sumPayments(array $paymentAmounts): string
    {
        $sum = '0.00';
        foreach ($paymentAmounts as $amount) {
            $amt = $this->normalizeAmount((string) $amount);
            if (bccomp($amt, '0', 2) < 0) {
                throw new \InvalidArgumentException('Payment amounts cannot be negative.');
            }
            $sum = bcadd($sum, $amt, 2);
        }
        return $sum;
    }

    public function paymentStatus(string $total, string $paid): string
    {
        if (bccomp($paid, '0', 2) <= 0) {
            return 'issued';
        }
        if (bccomp($paid, $total, 2) >= 0) {
            return 'paid';
        }
        return 'partially_paid';
    }

    private function normalizeAmount(string $value): string
    {
        $value = str_replace(',', '', trim($value));
        if ($value === '' || !is_numeric($value)) {
            return '0.00';
        }
        return number_format((float) $value, 2, '.', '');
    }
}
