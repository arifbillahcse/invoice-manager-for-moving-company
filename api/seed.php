<?php
// Loads sample data into the database (replaces existing data)
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonOut(['error' => 'POST only'], 405);
}

$db = getDB();
$db->beginTransaction();

try {
    // Wipe existing data (order matters due to foreign keys)
    $db->exec("DELETE FROM company_invoice_items");
    $db->exec("DELETE FROM company_invoices");
    $db->exec("DELETE FROM driver_invoice_items");
    $db->exec("DELETE FROM driver_invoices");
    $db->exec("DELETE FROM drivers");
    $db->exec("DELETE FROM companies");

    // Reset auto-increment
    $db->exec("ALTER TABLE companies              AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE drivers                AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE company_invoices       AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE company_invoice_items  AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE driver_invoices        AUTO_INCREMENT = 1");
    $db->exec("ALTER TABLE driver_invoice_items   AUTO_INCREMENT = 1");

    // ── Sample companies ───────────────────────
    $c = $db->prepare(
        "INSERT INTO companies (name, address, city, phone, dot_number, mc_number) VALUES (?,?,?,?,?,?)"
    );
    $c->execute(['BH Relocation INC',  '11723 Amber Park DR Suite 160', 'Alpharetta, GA 30009', '(770) 123-4567', '2521000', '875158']);
    $c->execute(['Prime Relocations',  '5695 Oakbrook Parkway, Suite D','Norcross, GA 30093',   '(770) 954-7095', '806005',  '358641']);

    // ── Sample drivers ─────────────────────────
    $d = $db->prepare(
        "INSERT INTO drivers (first_name, last_name, phone, license) VALUES (?,?,?,?)"
    );
    $d->execute(['BAKARY', 'Diallo',  '(770) 555-0001', 'DL001']);
    $d->execute(['JOHN',   'Doe',     '(770) 555-0002', 'DL002']);
    $d->execute(['Joseph', 'Smith',   '(770) 555-0003', 'DL003']);
    $d->execute(['Ahmed',  'Hassan',  '(404) 555-0004', 'DL004']);

    // ── Sample company invoice ─────────────────
    $ci = $db->prepare(
        "INSERT INTO company_invoices (company_id, date, subtotal, carrier_fee, total) VALUES (?,?,?,?,?)"
    );
    $ci->execute([1, '2026-03-01', 6900, 690, 7590]);
    $ciId = (int)$db->lastInsertId();

    $cii = $db->prepare(
        "INSERT INTO company_invoice_items
         (invoice_id, sort_order, job_number, driver_id, customer_name, from_location, to_location, cubic_feet, rate, balance_due, new_balance, remarks)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $cii->execute([$ciId, 0, 'CI001', 1, 'TUSTIN', 'Atlanta, GA',  'Miami, FL',  200,  2.50, 100, 0,   '']);
    $cii->execute([$ciId, 1, 'CI002', 2, 'Sara',   'Norcross, GA', 'Tampa, FL',  3200, 2.00, 500, 200, 'Paid partial']);

    // ── Sample driver invoice ──────────────────
    $di = $db->prepare(
        "INSERT INTO driver_invoices (driver_id, date, subtotal, carrier_fee, total) VALUES (?,?,?,?,?)"
    );
    $di->execute([1, '2026-03-02', 2562.5, 256.25, 2818.75]);
    $diId = (int)$db->lastInsertId();

    $dii = $db->prepare(
        "INSERT INTO driver_invoice_items
         (invoice_id, sort_order, job_number, company_id, customer_name, from_location, to_location, cubic_feet, rate, balance_due, new_balance, remarks)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
    );
    $dii->execute([$diId, 0, 'DI001', 1, 'Carter', 'Marietta, GA', 'Nashville, TN', 600, 1.50, 200, 0, '']);
    $dii->execute([$diId, 1, 'DI002', 2, 'Rivera', 'Atlanta, GA',  'Charlotte, NC', 950, 1.75, 0,   0, 'Fully paid']);

    $db->commit();
    jsonOut(['ok' => true]);

} catch (Throwable $e) {
    $db->rollBack();
    jsonOut(['error' => $e->getMessage()], 500);
}
