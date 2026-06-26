#!/bin/sh
# Load test gRPC EncryptionService - tiap request = proses PHP CLI terpisah
# Menghindari masalah grpc C-core yang tidak fork-safe (pcntl_fork)
#
# Penggunaan:
#   sh run_load_test.sh [total_requests] [concurrency]
#   contoh: sh run_load_test.sh 1000 50

TOTAL=${1:-1000}
CONCURRENCY=${2:-50}
RESULT_FILE="/tmp/grpc_load_test_$$.ndjson"

rm -f "$RESULT_FILE"

echo "=========================================="
echo " Load Test gRPC EncryptionService"
echo "=========================================="
echo "Total request : $TOTAL"
echo "Concurrency    : $CONCURRENCY"
echo "=========================================="
echo ""

START=$(date +%s.%N)

seq 1 "$TOTAL" | xargs -P "$CONCURRENCY" -I {} php grpc_worker.php {} >> "$RESULT_FILE"

END=$(date +%s.%N)
DURATION=$(echo "$END - $START" | bc)

TOTAL_DONE=$(wc -l < "$RESULT_FILE")
SUCCESS=$(grep -c '"status":"OK"' "$RESULT_FILE")
FAIL=$(grep -c '"status":"FAIL"' "$RESULT_FILE")

echo ""
echo "=========================================="
echo " HASIL LOAD TEST"
echo "=========================================="
echo "Total request     : $TOTAL_DONE / $TOTAL"
echo "Sukses             : $SUCCESS"
echo "Gagal              : $FAIL"
echo "Total durasi test  : ${DURATION} detik"
echo ""

# Statistik durasi pakai php (lebih mudah parsing JSON daripada sh murni)
php -r '
$lines = file($argv[1], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$durations = [];
$fails = [];
foreach ($lines as $line) {
    $d = json_decode($line, true);
    if (!$d) continue;
    $durations[] = $d["duration"];
    if ($d["status"] === "FAIL") $fails[] = $d;
}
sort($durations);
$count = count($durations);
if ($count > 0) {
    function pct($arr, $p) {
        $c = count($arr);
        $i = (int) ceil($p / 100 * $c) - 1;
        $i = max(0, min($c - 1, $i));
        return $arr[$i];
    }
    echo "--- Latency (ms) ---\n";
    echo "Min  : " . min($durations) . "\n";
    echo "Max  : " . max($durations) . "\n";
    echo "Avg  : " . round(array_sum($durations) / $count, 2) . "\n";
    echo "p50  : " . pct($durations, 50) . "\n";
    echo "p90  : " . pct($durations, 90) . "\n";
    echo "p99  : " . pct($durations, 99) . "\n";
}
if (count($fails) > 0) {
    echo "\n--- Contoh error (max 5) ---\n";
    foreach (array_slice($fails, 0, 5) as $f) {
        echo "  #{$f["id"]}: {$f["error"]}\n";
    }
}
' "$RESULT_FILE"

echo "=========================================="

rm -f "$RESULT_FILE"