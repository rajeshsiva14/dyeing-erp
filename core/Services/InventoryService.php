<?php
namespace Core\Services;

use Core\Database\Database;
use Exception;

class InventoryService {
    /**
     * Post an immutable stock movement
     */
    public static function postMovement(array $data): int {
        $required = ['company_id', 'warehouse_id', 'product_id', 'movement_type', 'quantity_base_uom', 'source_type', 'source_id'];
        foreach ($required as $f) {
            if (!isset($data[$f])) {
                throw new Exception("Missing required inventory movement field: {$f}");
            }
        }

        $movementId = Database::insert('inventory_movements', [
            'company_id'         => $data['company_id'],
            'warehouse_id'       => $data['warehouse_id'],
            'bin_id'             => $data['bin_id'] ?? null,
            'product_id'         => $data['product_id'],
            'lot_id'             => $data['lot_id'] ?? null,
            'roll_id'            => $data['roll_id'] ?? null,
            'owner_type'         => $data['owner_type'] ?? 'company',
            'owner_id'           => $data['owner_id'] ?? null,
            'movement_type'      => $data['movement_type'],
            'quantity_base_uom'  => $data['quantity_base_uom'],
            'unit_cost'          => $data['unit_cost'] ?? 0.0000,
            'total_cost'         => round(($data['quantity_base_uom'] ?? 0) * ($data['unit_cost'] ?? 0), 2),
            'source_type'        => $data['source_type'],
            'source_id'          => $data['source_id'],
            'source_line_id'     => $data['source_line_id'] ?? null,
            'occurred_at'        => date('Y-m-d H:i:s'),
            'posted_by'          => $data['posted_by'] ?? null,
        ]);

        // Update product current_stock if company owned with row-level lock and negative stock guard
        if (($data['owner_type'] ?? 'company') === 'company') {
            $stmt = Database::getConnection()->prepare("SELECT current_stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$data['product_id']]);
            $currentStock = (float)$stmt->fetchColumn();

            if ($data['quantity_base_uom'] < 0 && ($currentStock + $data['quantity_base_uom']) < -0.0001 && empty($data['allow_negative'])) {
                throw new Exception(sprintf(
                    "Insufficient inventory balance for Product #%d. Available stock: %.2f, Requested issue: %.2f.",
                    $data['product_id'],
                    $currentStock,
                    abs($data['quantity_base_uom'])
                ));
            }

            Database::getConnection()->prepare(
                "UPDATE products SET current_stock = current_stock + :qty WHERE id = :id"
            )->execute([
                'qty' => $data['quantity_base_uom'],
                'id'  => $data['product_id']
            ]);
        }

        // Update lot available_qty if lot specified
        if (!empty($data['lot_id'])) {
            Database::getConnection()->prepare(
                "UPDATE inventory_lots SET available_qty = available_qty + :qty WHERE id = :id"
            )->execute([
                'qty' => $data['quantity_base_uom'],
                'id'  => $data['lot_id']
            ]);
        }

        return $movementId;
    }

    /**
     * Get real-time stock balance calculated directly from immutable movements
     */
    public static function getStockBalance(int $productId, ?int $warehouseId = null): float {
        $sql = "SELECT COALESCE(SUM(quantity_base_uom), 0) FROM inventory_movements WHERE product_id = ?";
        $params = [$productId];
        if ($warehouseId) {
            $sql .= " AND warehouse_id = ?";
            $params[] = $warehouseId;
        }
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return (float)$stmt->fetchColumn();
    }
}
