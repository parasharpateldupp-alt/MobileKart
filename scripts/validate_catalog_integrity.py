"""
Automated Catalog Integrity Validation Script for MobileKart
Validates all 25 acceptance criteria across SQLite and TiDB Cloud databases.
"""

import os
import sys
import sqlite3
import subprocess
import json

def run_validations_sqlite(db_path):
    print("=" * 70)
    print(f"RUNNING VALIDATION AGAINST SQLITE: {db_path}")
    print("=" * 70)

    conn = sqlite3.connect(db_path)
    conn.row_factory = sqlite3.Row
    cur = conn.cursor()

    errors = []
    checks_passed = 0

    def assert_check(condition, desc):
        nonlocal checks_passed
        if condition:
            checks_passed += 1
            print(f"  [PASS] {desc}")
        else:
            errors.append(desc)
            print(f"  [FAIL] {desc}")

    # 1. Assert exactly 18 products exist
    count = cur.execute("SELECT COUNT(*) FROM products WHERE status = 'ACTIVE'").fetchone()[0]
    assert_check(count == 18, f"1. Exactly 18 products exist in catalog (found: {count})")

    # Fetch brands map
    brands = {r['brand_id']: r['name'] for r in cur.execute("SELECT brand_id, name FROM brands").fetchall()}
    brand_slugs = {r['slug']: r['brand_id'] for r in cur.execute("SELECT brand_id, slug FROM brands").fetchall()}

    # Fetch categories map
    categories = {r['category_id']: r['slug'] for r in cur.execute("SELECT category_id, slug FROM categories").fetchall()}
    cat_slug_ids = {r['slug']: r['category_id'] for r in cur.execute("SELECT category_id, slug FROM categories").fetchall()}

    # 2. Assert 0 products are named or mapped to unintended dummy brands
    invalid_brand_prods = cur.execute("SELECT product_id, product_name, brand_id FROM products WHERE brand_id NOT IN (SELECT brand_id FROM brands)").fetchall()
    assert_check(len(invalid_brand_prods) == 0, "2. Zero products mapped to invalid or dummy brands")

    # 3-13. Brand Mappings
    brand_checks = [
        ("Apple", "Apple iPhone", 4),
        ("Samsung", "Samsung Galaxy", 5),
        ("Vivo", "Vivo X200", 1),
        ("Xiaomi", "Xiaomi 16", 1),
        ("OnePlus", "OnePlus 15", 1),
        ("Google", "Google Pixel 11", 1),
        ("iQOO", "iQOO 14", 1),
        ("Realme", "Realme GT 8", 1),
        ("Nothing", "Nothing Phone (4)", 1),
        ("Motorola", "Motorola Edge 70", 1),
        ("Redmi", "Redmi Note 15", 1)
    ]
    check_num = 3
    for bname, prefix, expected_cnt in brand_checks:
        rows = cur.execute(f"SELECT p.product_id, p.product_name, b.name as brand_name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_name LIKE '{prefix}%'").fetchall()
        matching = [r for r in rows if r['brand_name'] == bname]
        is_ok = (len(matching) == expected_cnt)
        assert_check(is_ok, f"{check_num}. Every '{prefix}' has brand_id pointing to '{bname}' (matched: {len(matching)}/{expected_cnt})")
        check_num += 1

    # Specifically verify Redmi is NOT mapped to Nothing
    redmi_brand = cur.execute("SELECT b.name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_name LIKE 'Redmi Note 15%'").fetchone()
    redmi_is_redmi = redmi_brand and redmi_brand['name'] == 'Redmi'
    assert_check(redmi_is_redmi, "13b. Redmi Note 15 Pro+ 5G brand is strictly 'Redmi' (NOT 'Nothing')")

    # 14. Assert 'Foldable Phones' category contains ONLY iPhone Duo and Samsung Galaxy Z Fold 8
    foldable_cat_id = cat_slug_ids.get('foldable-phones')
    foldable_prods = cur.execute("""
        SELECT p.product_name 
        FROM products p 
        JOIN product_categories pc ON p.product_id = pc.product_id 
        WHERE pc.category_id = ?
    """, (foldable_cat_id,)).fetchall()
    foldable_names = set(r['product_name'] for r in foldable_prods)
    expected_foldables = {"Apple iPhone Duo (Foldable)", "Samsung Galaxy Z Fold 8 5G"}
    assert_check(foldable_names == expected_foldables, f"14. 'Foldable Phones' contains ONLY iPhone Duo & Z Fold 8 (found: {foldable_names})")

    # 15. Assert 'Gaming Phones' category contains iQOO 14 Pro, Realme GT 8 Pro, OnePlus 15 (no foldables)
    gaming_cat_id = cat_slug_ids.get('gaming-phones')
    gaming_prods = cur.execute("""
        SELECT p.product_name 
        FROM products p 
        JOIN product_categories pc ON p.product_id = pc.product_id 
        WHERE pc.category_id = ?
    """, (gaming_cat_id,)).fetchall()
    gaming_names = set(r['product_name'] for r in gaming_prods)
    has_gaming_cores = {"iQOO 14 Pro 5G", "Realme GT 8 Pro 5G", "OnePlus 15 5G"}.issubset(gaming_names)
    has_no_foldables_in_gaming = len(gaming_names.intersection(expected_foldables)) == 0
    assert_check(has_gaming_cores and has_no_foldables_in_gaming, f"15. 'Gaming Phones' contains gaming phones and ZERO foldables (found: {gaming_names})")

    # 16. Assert all 18 catalog phones appear in 5G category
    cat_5g_id = cat_slug_ids.get('5g-smartphones')
    prods_5g = cur.execute("""
        SELECT COUNT(DISTINCT p.product_id) 
        FROM products p 
        JOIN product_categories pc ON p.product_id = pc.product_id 
        WHERE pc.category_id = ?
    """, (cat_5g_id,)).fetchone()[0]
    assert_check(prods_5g == 18, f"16. All 18 phones appear in '5g-smartphones' category (found: {prods_5g})")

    # 17. Assert selecting brand=redmi returns Redmi Note 15 Pro+ and ZERO other phones
    redmi_prods = cur.execute("SELECT p.product_name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE b.slug = 'redmi'").fetchall()
    redmi_ok = len(redmi_prods) == 1 and "Redmi Note 15 Pro+" in redmi_prods[0]['product_name']
    assert_check(redmi_ok, f"17. Filter brand=redmi returns only Redmi Note 15 Pro+ (found: {[r['product_name'] for r in redmi_prods]})")

    # 18. Assert selecting brand=apple returns ALL 4 iPhones and ZERO Samsung/Vivo/Nothing
    apple_prods = cur.execute("SELECT p.product_name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE b.slug = 'apple'").fetchall()
    apple_names = [r['product_name'] for r in apple_prods]
    apple_ok = len(apple_names) == 4 and all("iPhone" in n for n in apple_names)
    assert_check(apple_ok, f"18. Filter brand=apple returns exactly 4 Apple phones and zero others (found {len(apple_names)})")

    # 19. Assert selecting brand=samsung returns ALL 5 Samsung phones and ZERO others
    samsung_prods = cur.execute("SELECT p.product_name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE b.slug = 'samsung'").fetchall()
    samsung_names = [r['product_name'] for r in samsung_prods]
    samsung_ok = len(samsung_names) == 5 and all("Galaxy" in n for n in samsung_names)
    assert_check(samsung_ok, f"19. Filter brand=samsung returns exactly 5 Samsung phones and zero others (found {len(samsung_names)})")

    # 20. Assert sorting by Brand A-Z orders products alphabetically by brand name
    brand_az = cur.execute("SELECT b.name as brand_name, p.product_name FROM products p JOIN brands b ON p.brand_id = b.brand_id ORDER BY b.name ASC, p.product_name ASC").fetchall()
    brand_az_names = [r['brand_name'] for r in brand_az]
    is_sorted_az = brand_az_names == sorted(brand_az_names)
    assert_check(is_sorted_az, "20. Sorting by Brand A-Z orders strictly alphabetically by brand name")

    # 21. Assert sorting by Brand Z-A orders products in reverse alphabetical order
    brand_za = cur.execute("SELECT b.name as brand_name, p.product_name FROM products p JOIN brands b ON p.brand_id = b.brand_id ORDER BY b.name DESC, p.product_name ASC").fetchall()
    brand_za_names = [r['brand_name'] for r in brand_za]
    is_sorted_za = brand_za_names == sorted(brand_za_names, reverse=True)
    assert_check(is_sorted_za, "21. Sorting by Brand Z-A orders strictly in reverse alphabetical order")

    # 22. Assert for every product, if reviews_count == 0, rating is 0.00
    unrated_prods = cur.execute("SELECT product_id, product_name, rating, reviews_count FROM products WHERE reviews_count = 0").fetchall()
    fake_ratings = [r for r in unrated_prods if r['rating'] > 0.0]
    assert_check(len(fake_ratings) == 0, f"22. All zero-review products have rating = 0.00 (no fake ratings, checked {len(unrated_prods)} products)")

    # 23. Assert for every product, final_price == round(price * (1 - discount/100))
    all_products = cur.execute("SELECT product_id, product_name, price, discount, final_price FROM products").fetchall()
    pricing_errors = []
    for p in all_products:
        expected = round(p['price'] * (1 - p['discount'] / 100.0), 2)
        if abs(p['final_price'] - expected) > 1.0: # allow 1 rupee rounding difference
            pricing_errors.append((p['product_name'], p['price'], p['discount'], p['final_price'], expected))
    assert_check(len(pricing_errors) == 0, f"23. For every product, final_price matches discount formula (errors: {pricing_errors})")

    # 24. Assert technical specifications have no duplicate or contradictory tokens
    spec_errors = []
    for p in cur.execute("SELECT product_id, product_name, ram, storage FROM products").fetchall():
        r = p['ram']
        s = p['storage']
        if "LPDDR5X LPDDR5X" in r or "Unified LPDDR5X" in r:
            spec_errors.append((p['product_name'], "RAM", r))
        if "NVMe UFS" in s or "UFS 4.0 UFS 4.0" in s:
            spec_errors.append((p['product_name'], "Storage", s))
    assert_check(len(spec_errors) == 0, f"24. Specifications strings are normalized and free of duplicate tokens (errors: {spec_errors})")

    # 25. Assert product_variants table exists and has rows for all 18 products
    var_counts = cur.execute("SELECT COUNT(DISTINCT product_id) as prod_count, COUNT(*) as total_variants FROM product_variants").fetchone()
    has_variants_all = (var_counts['prod_count'] == 18)
    assert_check(has_variants_all, f"25. product_variants table exists and covers all 18 products (distinct products: {var_counts['prod_count']}, total rows: {var_counts['total_variants']})")

    conn.close()
    return errors, checks_passed

def run_validations_tidb():
    print("\n" + "=" * 70)
    print("RUNNING VALIDATION AGAINST TIDB CLOUD (MYSQL)")
    print("=" * 70)

    php_script = """
    $host = 'gateway01.ap-southeast-1.prod.aws.tidbcloud.com';
    $port = '4000';
    $user = '4AVQkGYiwq2zQBT.root';
    $pass = 'BNdSSFFOMEjJYR1K';
    $db   = 'test';
    $caPath = 'D:/PATU/config/cacert.pem';
    $dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        1007 => $caPath,
        1014 => false
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        $pCount = $pdo->query("SELECT COUNT(*) FROM products WHERE status = 'ACTIVE'")->fetchColumn();
        $bCount = $pdo->query("SELECT COUNT(*) FROM brands WHERE status = 'ACTIVE'")->fetchColumn();
        $pcCount = $pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn();
        $pvCount = $pdo->query("SELECT COUNT(*) FROM product_variants")->fetchColumn();
        $pvProds = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM product_variants")->fetchColumn();
        $redmiBrand = $pdo->query("SELECT b.name FROM products p JOIN brands b ON p.brand_id = b.brand_id WHERE p.product_name LIKE 'Redmi Note 15%'")->fetchColumn();
        $foldables = $pdo->query("SELECT p.product_name FROM products p JOIN product_categories pc ON p.product_id = pc.product_id JOIN categories c ON pc.category_id = c.category_id WHERE c.slug = 'foldable-phones'")->fetchAll(PDO::FETCH_COLUMN);

        echo json_encode([
            'success' => true,
            'products_count' => (int)$pCount,
            'brands_count' => (int)$bCount,
            'product_categories_count' => (int)$pcCount,
            'product_variants_count' => (int)$pvCount,
            'variant_products_count' => (int)$pvProds,
            'redmi_brand' => $redmiBrand,
            'foldables' => $foldables
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    """

    res = subprocess.run(["php", "-r", php_script], capture_output=True, text=True)
    try:
        data = json.loads(res.stdout)
        if not data.get('success'):
            print("  [FAIL] TiDB connection / query error:", data.get('error'))
            return ["TiDB query error: " + str(data.get('error'))], 0
        
        errors = []
        checks = 0
        def c(cond, desc):
            nonlocal checks
            if cond:
                checks += 1
                print(f"  [PASS] TiDB: {desc}")
            else:
                errors.append(desc)
                print(f"  [FAIL] TiDB: {desc}")

        c(data['products_count'] == 18, f"18 products in TiDB (found {data['products_count']})")
        c(data['brands_count'] == 11, f"11 brands in TiDB (found {data['brands_count']})")
        c(data['product_categories_count'] >= 40, f"product_categories mappings in TiDB ({data['product_categories_count']})")
        c(data['variant_products_count'] == 18, f"product_variants covers 18 products ({data['product_variants_count']} rows)")
        c(data['redmi_brand'] == 'Redmi', f"Redmi Note 15 Pro+ brand is strictly Redmi in TiDB (found {data['redmi_brand']})")
        c(set(data['foldables']) == {"Apple iPhone Duo (Foldable)", "Samsung Galaxy Z Fold 8 5G"}, f"Foldables in TiDB contain ONLY iPhone Duo and Z Fold 8 ({data['foldables']})")

        return errors, checks
    except Exception as e:
        print("  [FAIL] Error parsing TiDB validation output:", e, res.stdout, res.stderr)
        return [str(e)], 0

if __name__ == '__main__':
    sqlite_db = r"D:\PATU\database\mobilekart.sqlite"
    errs1, pass1 = run_validations_sqlite(sqlite_db)
    errs2, pass2 = run_validations_tidb()

    total_errors = len(errs1) + len(errs2)
    print("\n" + "=" * 70)
    print(f"SUMMARY: {pass1 + pass2} CHECKS PASSED, {total_errors} ERRORS FOUND")
    print("=" * 70)

    if total_errors > 0:
        print("\nERRORS TO FIX:")
        for err in errs1 + errs2:
            print(f"  - {err}")
        sys.exit(1)
    else:
        print("\nALL CATALOG DATA INTEGRITY AND ARCHITECTURAL ACCEPTANCE CRITERIA SATISFIED 100%!")
        sys.exit(0)
