-- ============================================================
-- Migration: Convert single-product orders → multi-product
-- Run ONCE before launching the updated application.
-- ============================================================

-- 1. Create the order_details table
CREATE TABLE IF NOT EXISTS order_details (
    id         BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id   BIGINT NOT NULL,
    product_id BIGINT NOT NULL,
    quantity   INT    NOT NULL,
    unit_price DOUBLE NOT NULL,
    subtotal   DOUBLE NOT NULL,
    FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- 2. Migrate every existing order row into order_details
INSERT INTO order_details (order_id, product_id, quantity, unit_price, subtotal)
SELECT id, product_id, quantity, unit_price, total_price
FROM   orders
WHERE  product_id IS NOT NULL;

-- 3. Drop the single-product columns from the orders header table
ALTER TABLE orders
    DROP COLUMN IF EXISTS product_id,
    DROP COLUMN IF EXISTS seller_id,
    DROP COLUMN IF EXISTS quantity,
    DROP COLUMN IF EXISTS unit_price;
