<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * The report queries of the Yii app's Report model, kept as SQL. `{{name}}`
 * is the prefixed table name. Where Yii pasted request values into the SQL
 * they are cast to int or bound here; the results are the same.
 *
 * Every method returns rows as associative arrays, like CDbCommand::queryAll().
 */
class Reports
{
    /**
     * In / out / on-hand quantities and amounts per product. A product filter
     * replaces (does not narrow) a category filter, as in Yii.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function stockSummary(mixed $categoryId, mixed $itemId, mixed $store): array
    {
        [$criteria, $pr, $si, $inv, $ss] = self::stockCriteria($categoryId, $itemId, $store);

        return self::select('SELECT cat.`title` AS category, itm.`title` AS item, itm.`minimum_storage_limit` AS minimum_quantity, IFNULL(AIN.quantity,0) AS in_quantity, IFNULL(AIN.amount,0.00) AS in_amount, (IFNULL(BOUT.quantity,0) + IFNULL(IOUT.quantity,0)) AS out_quantity, (IFNULL(BOUT.amount,0.00) + IFNULL(IOUT.amount,0.00)) AS out_amount, IFNULL(AVL.quantity,0) AS avl_quantity, IFNULL(AVL.amount,0.00) AS avl_amount
            FROM {{product}} itm
            LEFT OUTER JOIN (
                SELECT pr.item AS item, IFNULL(SUM(pr.quantity),0) AS quantity, IFNULL(SUM(pr.buy_amount),0.00) AS amount
                FROM {{purchase_receive}} pr LEFT OUTER JOIN {{purchase_receive_parent}} prp ON pr.parent=prp.id
                WHERE prp.status = 1 '.$pr.' GROUP BY pr.item) AIN ON AIN.item=itm.id
            LEFT OUTER JOIN (
                SELECT si.item AS item, IFNULL(SUM(si.quantity),0) AS quantity, IFNULL(SUM(si.amount),0.00) AS amount
                FROM {{stock_issue}} si LEFT OUTER JOIN {{stock_issue_parent}} sip ON si.parent=sip.id
                WHERE sip.status = 1 '.$si.' GROUP BY si.item) BOUT ON BOUT.item=itm.id
            LEFT OUTER JOIN (
                SELECT inv.item AS item, IFNULL(SUM(inv.quantity),0) AS quantity, IFNULL(SUM(inv.amount),0.00) AS amount
                FROM {{invoice}} inv LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
                WHERE inv.servicetype="Medicine" AND invp.status = 1 '.$inv.' GROUP BY inv.item) IOUT ON IOUT.item=itm.id
            LEFT OUTER JOIN (
                SELECT ss.item AS item, IFNULL(SUM(ss.quantity),0) AS quantity, IFNULL(SUM(ss.amount),0.00) AS amount
                FROM {{stock_summary}} ss '.$ss.' GROUP BY ss.item) AVL ON AVL.item=itm.id
            LEFT OUTER JOIN {{product_category}} cat ON itm.category=cat.id
            WHERE itm.title IS NOT NULL'.$criteria);
    }

    /**
     * Received lines of received MRRs in a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function stockReceive(mixed $categoryId, mixed $itemId, string $startDate, string $endDate): array
    {
        $criteria = ((int) $categoryId !== 0 ? ' AND itm.category='.(int) $categoryId : '')
            .((int) $itemId !== 0 ? ' AND itm.id='.(int) $itemId : '');

        return self::select('SELECT prp.`receive_number` AS receive_number, prp.`receive_date` AS receive_date, sup.`title` AS supplier, pr.item AS itemid, itm.`title` AS item, pr.`quantity` AS quantity, pr.`buy_rate` AS rate, pr.`buy_amount` AS total, str.id AS storeid, str.title AS store, bat.title AS batch, bat.expiry AS expiry, pr.store AS storeid, pr.batch AS batchid
            FROM {{purchase_receive}} pr
            LEFT OUTER JOIN {{purchase_receive_parent}} prp ON pr.parent=prp.id
            LEFT OUTER JOIN {{vendor}} sup ON prp.supplier=sup.id
            LEFT OUTER JOIN {{product}} itm ON pr.item=itm.id
            LEFT OUTER JOIN {{store}} str ON pr.store=str.id
            LEFT OUTER JOIN {{batch}} bat ON pr.batch=bat.id
            WHERE prp.status = 1 AND prp.`receive_date` >= ? AND prp.`receive_date` < ?'.$criteria.' ORDER BY item ASC', self::range($startDate, $endDate));
    }

    /**
     * Sold quantity and amount per invoice and product/service (approved invoices).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function sales(string $startDate, string $endDate, mixed $type, mixed $service, mixed $product, mixed $category, mixed $status): array
    {
        $criteria = '';
        $bindings = self::range($startDate, $endDate);

        if ($category != null) {
            $criteria .= ' AND invp.patient_category='.(int) $category;
        }
        if ($status != null) {
            $criteria .= ' AND invp.payment_status=?';
            $bindings[] = (string) $status;
        }
        if ($type != null) {
            if ($type == 'Service' && $service != null) {
                $criteria .= ' AND inv.service='.(int) $service;
            } elseif ($type == 'Medicine' && $product != null) {
                $criteria .= ' AND inv.item='.(int) $product;
            } else {
                $criteria .= ' AND inv.servicetype=?';
                $bindings[] = (string) $type;
            }
        }

        return self::select('SELECT invp.invoice_number, invp.patient, inv.created_on, inv.servicetype, inv.service, inv.item, SUM(inv.quantity) AS sold, SUM(inv.amount) AS amount, invp.patient_category, invp.payment_status FROM {{invoice}} inv
            LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
            WHERE invp.status=1 AND inv.created_on >= ? AND inv.created_on < ? '.$criteria.'
            GROUP BY invp.invoice_number, inv.item, inv.service ORDER BY inv.created_on ASC', $bindings);
    }

    /**
     * Stock expired (expiry 0) or expiring within the given number of days.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function expiration(mixed $item, mixed $store, mixed $expiry): array
    {
        $criteria = (! empty($item) ? ' AND ss.item='.(int) $item : '').(! empty($store) ? ' AND ss.store='.(int) $store : '');
        $bindings = [];

        if ($expiry == 0) {
            $criteria .= ' AND bt.expiry < CURDATE()';
        } else {
            $maxExpiry = date('Y-m-d 00:00:00', strtotime('+'.(int) $expiry.' days'));
            $criteria .= ' AND bt.expiry >= CURDATE() AND bt.expiry < ?';
            $bindings[] = date('Y-m-d 00:00:00', strtotime($maxExpiry.' +1 day'));
        }

        return self::select('SELECT st.id AS storeid, st.title AS store, it.title AS product, ss.batch, ss.quantity, ss.rate, ss.amount, um.formal_name AS unit, bt.`expiry` AS expiry
            FROM {{stock_summary}} ss
            LEFT OUTER JOIN {{batch}} bt ON ss.batch=bt.id
            LEFT OUTER JOIN {{product}} it ON ss.item=it.id
            LEFT OUTER JOIN {{store}} st ON ss.store=st.id
            LEFT OUTER JOIN {{unit}} um ON it.unit=um.id
            WHERE ss.quantity>= 1 '.$criteria.' ORDER BY bt.`expiry` ASC', $bindings);
    }

    /**
     * Registration-fee lines (services 16 and 201) of invoices in a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function patientRegister(string $startDate, string $endDate, mixed $category, mixed $categoryNew): array
    {
        $criteria = (! empty($category) ? ' AND pat.category='.(int) $category : '').(! empty($categoryNew) ? ' AND pat.category_new='.(int) $categoryNew : '');

        return self::select('SELECT invp.invoice_date, pat.id AS id, pat.pat_id AS pid, pat.`category` AS category, pat.`category_new` AS category_new, pat.`ref_no`, pat.`name` AS pname, pat.`age`, pat.`sex`, CONCAT(pat.`emergency_name`,\'<br />\',pat.`emergency_contact`) AS emergency_person, pat.`emergency_relation`, pat.`address`, inv.amount, invp.`invoice_number`
            FROM {{invoice}} inv
            LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
            LEFT OUTER JOIN {{patient}} pat ON pat.id=invp.patient
            WHERE invp.invoice_date >= ? AND invp.invoice_date < ? AND inv.service IN (16,201) AND invp.patient !=0 '.$criteria.' ORDER BY invp.`invoice_date` ASC', self::range($startDate, $endDate));
    }

    /**
     * Prescriptions per diagnosis in a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function disease(string $startDate, string $endDate): array
    {
        return self::select('SELECT TRIM(`diagnosis`) AS diagnosis, COUNT(*) AS total FROM {{patient_prescription}}
            WHERE created_on >= ? AND created_on < ? GROUP BY `diagnosis` ORDER BY `diagnosis`', self::range($startDate, $endDate));
    }

    /**
     * Prescribed patients per sex and age group (0-5, 6-14, 15-24, 25+), sexes in descending order.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function patientAttendanceAge(string $startDate, string $endDate): array
    {
        return self::select('SELECT p.sex,
                SUM(IF(p.age <= 5,1,0)) as AGE_GROUP_1,
                SUM(IF(p.age BETWEEN 6 and 14,1,0)) as AGE_GROUP_2,
                SUM(IF(p.age BETWEEN 15 and 24,1,0)) as AGE_GROUP_3,
                SUM(IF(p.age BETWEEN 25 and 200,1,0)) as AGE_GROUP_4,
                COUNT(*) AS total
            FROM {{patient_prescription}} pp INNER JOIN {{patient}} p ON p.id = pp.patient
            WHERE pp.created_on >= ? AND pp.created_on <= ?
            GROUP BY p.sex ORDER BY p.sex DESC', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function patientAttendanceSex(string $startDate, string $endDate): array
    {
        return self::select('SELECT p.sex, COUNT(*) AS total
            FROM {{patient_prescription}} pp INNER JOIN {{patient}} p ON p.id = pp.patient
            WHERE pp.created_on >= ? AND pp.created_on <= ?
            GROUP BY p.sex ORDER BY p.sex DESC', [$startDate.' 00:00:00', $endDate.' 23:59:59']);
    }

    /**
     * Physician visit charge (services 16, 201) and medicine per approved invoice.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function medicineBill(string $startDate, string $endDate, mixed $category, mixed $categoryNew, mixed $service, mixed $status): array
    {
        [$criteria, $bindings] = self::billCriteria($category, $categoryNew, $service, $status);

        return self::select('SELECT A.created_on, A.`pat_id`, A.`ref_no`, A.`category_new`, A.`category`, A.`name`, A.amount_service, A.amount_medicine FROM
            (
            SELECT inv.created_on, pat.`pat_id`, pat.`ref_no`, pat.`category`, pat.`category_new`, pat.`name`,
                (SELECT IFNULL(SUM(invv.amount),0) FROM {{invoice}} invv WHERE invv.`parent` = inv.`parent` AND invv.`servicetype`="Service" AND invv.`service` IN(16,201)) AS amount_service,
                (SELECT IFNULL(SUM(invvv.amount),0) FROM {{invoice}} invvv WHERE invvv.`parent` = inv.`parent` AND invvv.`servicetype`="Medicine") AS amount_medicine
            FROM {{invoice}} inv
            LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
            LEFT OUTER JOIN {{patient}} pat ON invp.patient=pat.id
            WHERE invp.status=1 AND inv.created_on >= ? AND inv.created_on < ? '.$criteria.'
            GROUP BY inv.`parent` ORDER BY inv.created_on ASC
            ) A
            WHERE A.amount_service>0 OR A.amount_medicine>0', array_merge(self::range($startDate, $endDate), $bindings));
    }

    /**
     * Consultation and service amounts per approved invoice with services.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function serviceBill(string $startDate, string $endDate, mixed $category, mixed $categoryNew, mixed $service, mixed $status): array
    {
        [$criteria, $bindings] = self::billCriteria($category, $categoryNew, $service, $status);
        $consultation = $service != null ? ' AND (srvc.parent='.(int) $service.' OR srvc.id='.(int) $service.')' : '';
        $services = $service != null ? ' AND (srvs.parent='.(int) $service.' OR srvs.id='.(int) $service.')' : '';

        return self::select('SELECT A.created_on, A.note, A.`pat_id`, A.`ref_no`, A.`category_new`, A.`category`, A.`name`, A.`patient_grade`, A.amount_consultation, A.amount_service FROM
            (
            SELECT inv.created_on, inv.note, pat.`pat_id`, pat.`ref_no`, pat.`category_new`, pat.`category`, pat.`patient_grade`, pat.`name`,
                (SELECT IFNULL(SUM(invv.amount),0) FROM {{invoice}} invv WHERE invv.`parent` = inv.`parent` AND invv.`service` IN(SELECT srvc.id FROM {{service}} srvc WHERE srvc.`service_type` = "Consultation" '.$consultation.')) AS amount_consultation,
                (SELECT IFNULL(SUM(invvv.amount),0) FROM {{invoice}} invvv WHERE invvv.`parent` = inv.`parent` AND invvv.`service` IN(SELECT srvs.id FROM {{service}} srvs WHERE srvs.`service_type` = "Service" '.$services.')) AS amount_service
            FROM {{invoice}} inv
            LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
            LEFT OUTER JOIN {{patient}} pat ON invp.patient=pat.id
            WHERE invp.status=1 AND inv.`servicetype` = "Service" AND inv.created_on >= ? AND inv.created_on < ? '.$criteria.'
            GROUP BY inv.`parent` ORDER BY inv.created_on ASC
            ) A
            WHERE A.amount_consultation>0 OR A.amount_service>0', array_merge(self::range($startDate, $endDate), $bindings));
    }

    /**
     * Quantity and sale amount per medicine (approved invoices).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function medicineIncome(string $startDate, string $endDate, mixed $product): array
    {
        $criteria = $product != null ? ' AND inv.item='.(int) $product : '';

        return self::select('SELECT inv.item, SUM(inv.quantity) AS quantity, SUM(inv.amount) AS sale_amount FROM {{invoice}} inv
            LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
            WHERE invp.status=1 AND servicetype="Medicine" AND inv.created_on >= ? AND inv.created_on < ? '.$criteria.'
            GROUP BY inv.item', self::range($startDate, $endDate));
    }

    /**
     * Opening, in, out and closing quantities per product for a period.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function periodWiseStock(mixed $categoryId, mixed $itemId, mixed $store, string $startDate, string $endDate): array
    {
        [$criteria, $pr, $si, $inv, $ss] = self::stockCriteria($categoryId, $itemId, $store);
        $in = fn (string $alias, string $when) => 'LEFT OUTER JOIN (
                SELECT pr.item AS item, IFNULL(SUM(pr.quantity),0) AS quantity, IFNULL(SUM(pr.buy_amount),0.00) AS amount
                FROM {{purchase_receive}} pr LEFT OUTER JOIN {{purchase_receive_parent}} prp ON pr.parent=prp.id
                WHERE prp.status = 1 AND '.str_replace('#', 'pr', $when).' '.$pr.' GROUP BY pr.item) '.$alias.' ON '.$alias.'.item=itm.id ';
        $issued = fn (string $alias, string $when) => 'LEFT OUTER JOIN (
                SELECT si.item AS item, IFNULL(SUM(si.quantity),0) AS quantity, IFNULL(SUM(si.amount),0.00) AS amount
                FROM {{stock_issue}} si LEFT OUTER JOIN {{stock_issue_parent}} sip ON si.parent=sip.id
                WHERE sip.status = 1 AND '.str_replace('#', 'si', $when).' '.$si.' GROUP BY si.item) '.$alias.' ON '.$alias.'.item=itm.id ';
        $invoiced = fn (string $alias, string $when) => 'LEFT OUTER JOIN (
                SELECT inv.item AS item, IFNULL(SUM(inv.quantity),0) AS quantity, IFNULL(SUM(inv.amount),0.00) AS amount
                FROM {{invoice}} inv LEFT OUTER JOIN {{invoice_parent}} invp ON inv.parent=invp.id
                WHERE inv.servicetype="Medicine" AND invp.status = 1 AND '.str_replace('#', 'inv', $when).' '.$inv.' GROUP BY inv.item) '.$alias.' ON '.$alias.'.item=itm.id ';

        $before = '#.`created_on` < :start_date';
        $during = '#.`created_on` >= :start_date AND #.`created_on` < :end_date';
        $until = '#.`created_on` < :end_date';

        [$start, $end] = self::range($startDate, $endDate);

        return self::select('SELECT cat.`title` AS category, itm.`title` AS item,
                (IFNULL(OPAIN.quantity,0) - (IFNULL(OPBOUT.quantity,0)+IFNULL(OPIOUT.quantity,0))) AS opening_quantity,
                IFNULL(AIN.quantity,0) AS in_quantity, IFNULL(AIN.amount,0.00) AS in_amount,
                (IFNULL(BOUT.quantity,0) + IFNULL(IOUT.quantity,0)) AS out_quantity, (IFNULL(BOUT.amount,0.00) + IFNULL(IOUT.amount,0.00)) AS out_amount,
                IFNULL(AVL.quantity,0) AS avl_quantity, IFNULL(AVL.amount,0.00) AS avl_amount,
                (IFNULL(CLAIN.quantity,0) - (IFNULL(CLBOUT.quantity,0)+IFNULL(CLIOUT.quantity,0))) AS closing_quantity
            FROM {{product}} itm '
            .$in('OPAIN', $before).$issued('OPBOUT', $before).$invoiced('OPIOUT', $before)
            .$in('AIN', $during).$issued('BOUT', $during).$invoiced('IOUT', $during)
            .'LEFT OUTER JOIN (
                SELECT ss.item AS item, IFNULL(SUM(ss.quantity),0) AS quantity, IFNULL(SUM(ss.amount),0.00) AS amount
                FROM {{stock_summary}} ss '.$ss.' GROUP BY ss.item) AVL ON AVL.item=itm.id '
            .$in('CLAIN', $until).$issued('CLBOUT', $until).$invoiced('CLIOUT', $until)
            .'LEFT OUTER JOIN {{product_category}} cat ON itm.category=cat.id
            WHERE itm.title IS NOT NULL'.$criteria, ['start_date' => $start, 'end_date' => $end]);
    }

    /**
     * Invoices of patients in a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function patientInvoice(string $startDate, string $endDate, mixed $patient, mixed $status): array
    {
        $bindings = self::range($startDate, $endDate);
        $criteria = $patient != null ? ' AND invp.patient='.(int) $patient : '';
        if ($status != null) {
            $criteria .= ' AND invp.payment_status=?';
            $bindings[] = (string) $status;
        }

        return self::select('SELECT invp.invoice_number, invp.invoice_date, pat.id AS id, pat.pat_id AS pid, pat.`category` AS category, pat.`category_new` AS category_new, pat.`name` AS pname, invp.total_amount, invp.`payment_status`
            FROM {{invoice_parent}} invp
            LEFT OUTER JOIN {{patient}} pat ON pat.id=invp.patient
            WHERE invp.invoice_date >= ? AND invp.invoice_date < ? AND invp.patient !=0 '.$criteria.' ORDER BY invp.`invoice_date` DESC', $bindings);
    }

    /**
     * Consultation lines of invoices in a date range.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function patientRegisterPhysio(string $startDate, string $endDate, mixed $category = null, mixed $categoryNew = null, mixed $patientType = null): array
    {
        $criteria = (! empty($category) ? ' AND pat.category = '.(int) $category : '')
            .(! empty($categoryNew) ? ' AND pat.category_new = '.(int) $categoryNew : '')
            .(! empty($patientType) ? ' AND pat.patient_type = '.(int) $patientType : '');

        return self::select('SELECT invp.invoice_date, pat.id AS id, pat.pat_id AS pid, pat.category AS category, pat.category_new AS category_new,
                pat.name AS pname, pat.patient_grade, pat.age, pat.sex, pat.ref_no, pat.mobile, pat.problem,
                CONCAT(pat.emergency_name, "<br />", pat.emergency_contact) AS emergency_person,
                pat.emergency_relation, pat.address, inv.amount, invp.invoice_number
            FROM {{invoice_parent}} invp
            INNER JOIN {{invoice}} inv ON inv.parent = invp.id
            INNER JOIN {{service}} srv ON srv.id = inv.service AND srv.service_type = "Consultation"
            INNER JOIN {{patient}} pat ON pat.id = invp.patient
            WHERE invp.invoice_date >= ? AND invp.invoice_date < ? AND invp.patient != 0'.$criteria.'
            ORDER BY invp.invoice_date ASC', self::range($startDate, $endDate));
    }

    /**
     * Patients registered in a date range. The category filter includes its
     * sub categories. (Yii's patient type filter referred to an undefined
     * variable and never applied.)
     *
     * @return array<int, array<string, mixed>>
     */
    public static function patientContactRegister(string $startDate, string $endDate, mixed $category, mixed $categoryNew, mixed $admission): array
    {
        $bindings = self::range($startDate, $endDate);
        $criteria = ! empty($category) ? ' AND pat.category='.(int) $category : '';
        if (! empty($categoryNew)) {
            $criteria .= ' AND pat.category_new IN(SELECT s.id FROM {{patient_category_new}} s WHERE s.parent='.(int) $categoryNew.' OR s.id='.(int) $categoryNew.')';
        }
        if (! empty($admission)) {
            $criteria .= ' AND pat.admission=?';
            $bindings[] = (string) $admission;
        }

        return self::select('SELECT pat.`created_on`, pat.id AS id, pat.pat_id AS pid, pat.`category` AS category, pat.`category_new` AS category_new, pat.`name` AS pname, pat.`patient_grade`, pat.`age`, pat.`sex`, pat.`ref_no`, pat.`mobile`, pat.`referred`, pat.`problem`, CONCAT(pat.`emergency_name`,\'<br />\',pat.`emergency_contact`) AS emergency_person, pat.`emergency_relation`, pat.`address`
            FROM {{patient}} pat
            WHERE pat.created_on >= ? AND pat.created_on < ? '.$criteria.'
            ORDER BY pat.`created_on` ASC', $bindings);
    }

    /**
     * Prescriptions in a date range with their invoices; type 1 only those without one.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function invoiceByPrescription(string $startDate, string $endDate, mixed $patient, mixed $type): array
    {
        $criteria = ($patient != null ? ' AND invp.patient='.(int) $patient : '').($type == 1 ? ' AND invp.prescription IS NULL' : '');

        return self::select('SELECT patp.pre_number, invp.invoice_number, patp.created_on as created_on, pat.id AS id, pat.pat_id AS pid, pat.`category` AS category, pat.`category_new` AS category_new, pat.`name` AS pname, invp.total_amount, invp.`payment_status`
            FROM {{patient_prescription}} patp
            LEFT OUTER JOIN {{invoice_parent}} invp ON invp.prescription = patp.id
            LEFT OUTER JOIN {{patient}} pat ON pat.id = patp.patient
            WHERE patp.created_on >= ? AND patp.created_on < ? '.$criteria.' ORDER BY patp.`created_on` DESC', self::range($startDate, $endDate));
    }

    /**
     * [start of $startDate, start of the day after $endDate] — the reports
     * include the whole end day.
     *
     * @return array{0: string, 1: string}
     */
    private static function range(string $startDate, string $endDate): array
    {
        return [$startDate.' 00:00:00', date('Y-m-d 00:00:00', strtotime($endDate.' +1 day'))];
    }

    /**
     * @return array{0: string, 1: string, 2: string, 3: string, 4: string}
     */
    private static function stockCriteria(mixed $categoryId, mixed $itemId, mixed $store): array
    {
        $criteria = '';
        if (! empty($categoryId)) {
            $criteria = ' AND itm.category='.(int) $categoryId;
        }
        if (! empty($itemId)) {
            $criteria = ' AND itm.id='.(int) $itemId;
        }

        if (empty($store)) {
            return [$criteria, '', '', '', ''];
        }

        $store = (int) $store;

        return [$criteria, ' AND pr.store='.$store, ' AND si.store='.$store, ' AND inv.store='.$store, ' WHERE ss.store='.$store];
    }

    /**
     * @return array{0: string, 1: array<int, string>}
     */
    private static function billCriteria(mixed $category, mixed $categoryNew, mixed $service, mixed $status): array
    {
        $criteria = ($category != null ? ' AND invp.patient_category='.(int) $category : '')
            .($categoryNew != null ? ' AND invp.patient_category_new='.(int) $categoryNew : '')
            .($service != null ? ' AND inv.service IN(SELECT s.id FROM {{service}} s WHERE s.parent='.(int) $service.' OR s.id='.(int) $service.')' : '');

        if ($status != null) {
            return [$criteria.' AND invp.payment_status=?', [(string) $status]];
        }

        return [$criteria, []];
    }

    /**
     * @param  array<int|string, mixed>  $bindings
     * @return array<int, array<string, mixed>>
     */
    private static function select(string $sql, array $bindings = []): array
    {
        $sql = preg_replace_callback('/\{\{(\w+)\}\}/', fn ($m) => DB::getTablePrefix().$m[1], $sql);

        // Named parameters may repeat (Yii's emulated prepares allowed it):
        // turn them into positional ones for native prepares
        if ($bindings !== [] && ! array_is_list($bindings)) {
            $positional = [];
            $sql = preg_replace_callback('/:(\w+)/', function ($m) use ($bindings, &$positional) {
                $positional[] = $bindings[$m[1]];

                return '?';
            }, $sql);
            $bindings = $positional;
        }

        return array_map(fn ($row) => (array) $row, DB::select($sql, $bindings));
    }
}
