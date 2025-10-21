<?php

namespace App\Validators;

class OrderValidator
{
    public static function validate(array $data): array
    {
        $errors = [];

        // Required fields
        if (empty($data['product_id'])) {
            $errors[] = 'Product ID is required';
        } elseif (!is_numeric($data['product_id']) || $data['product_id'] <= 0) {
            $errors[] = 'Product ID must be a positive integer';
        }

        if (empty($data['quantity'])) {
            $errors[] = 'Quantity is required';
        } elseif (!is_numeric($data['quantity']) || $data['quantity'] <= 0) {
            $errors[] = 'Quantity must be a positive number';
        } elseif ($data['quantity'] > 1000) {
            $errors[] = 'Quantity cannot exceed 1000';
        }

        if (!isset($data['price'])) {
            $errors[] = 'Price is required';
        } elseif (!is_numeric($data['price']) || $data['price'] < 0) {
            $errors[] = 'Price must be a non-negative number';
        }

        // Validate date format if provided
        if (isset($data['date']) && !empty($data['date'])) {
            $date = \DateTime::createFromFormat('Y-m-d H:i:s', $data['date']);
            if (!$date || $date->format('Y-m-d H:i:s') !== $data['date']) {
                $errors[] = 'Date must be in format Y-m-d H:i:s';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}