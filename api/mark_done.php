<?php
require_once __DIR__ . '/../functions/helpers.php';
require_once __DIR__ . '/../config/database.php';

ensure_session_started();

header('Content-Type: application/json; charset=utf-8');

$npp = $_SESSION['npp'] ?? null;
if (empty($npp)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit;
}

// Support two modes:
// 1) Mark real pekerjaan row done by id (existing behavior)
// 2) Mark recurring master occurrence done by creating/updating a pekerjaan instance

$masterIdRaw = $_POST['master_tugas_id'] ?? null;
$occDateRaw = $_POST['tgl_mulai'] ?? null;
$tokenRaw = $_POST['token'] ?? null;
$isMasterMode = ($masterIdRaw !== null && $occDateRaw !== null);

if ($isMasterMode) {
    $masterId = (int) $masterIdRaw;
    $occDate = trim((string) $occDateRaw);
    $token = is_string($tokenRaw) ? trim($tokenRaw) : '';
    if ($masterId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid master_tugas_id']);
        http_response_code(400);
        exit;
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $occDate)) {
        echo json_encode(['success' => false, 'message' => 'Invalid tgl_mulai']);
        http_response_code(400);
        exit;
    }

    // Verify occurrence token to prevent client-side date manipulation
    if (!function_exists('worklog_verify_master_occurrence') || !worklog_verify_master_occurrence($masterId, $occDate, $token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid token']);
        http_response_code(400);
        exit;
    }

    // Detect optional column support: npp_manager on master_tugas
    $hasMasterNppManagerCol = false;
    $colRes = $conn->query("SHOW COLUMNS FROM master_tugas LIKE 'npp_manager'");
    if ($colRes) {
        $hasMasterNppManagerCol = ($colRes->num_rows > 0);
        $colRes->free();
    }

    // Load master data; ensure current user is the assignee (mt.npp)
    $masterSql = 'SELECT mt.id, mt.judul, mt.deskripsi, mt.npp, mt.periode';
    if ($hasMasterNppManagerCol) {
        $masterSql .= ', mt.npp_manager';
    }
    $masterSql .= ' FROM master_tugas mt WHERE mt.id = ? LIMIT 1';

    $stmtM = $conn->prepare($masterSql);
    if (!$stmtM) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
        http_response_code(500);
        exit;
    }
    $stmtM->bind_param('i', $masterId);
    $stmtM->execute();
    $resM = $stmtM->get_result();
    $mt = $resM ? $resM->fetch_assoc() : null;
    $stmtM->close();

    if (!$mt) {
        echo json_encode(['success' => false, 'message' => 'Master not found']);
        http_response_code(404);
        exit;
    }

    $assignedTo = (string) ($mt['npp'] ?? '');
    if ($assignedTo === '' || $assignedTo !== $npp) {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        http_response_code(403);
        exit;
    }

    // If an instance already exists for this master+date, just mark that row done
    // and ensure tgl_selesai is filled by system rules (not client input).
    $stmtE = $conn->prepare('SELECT id, assigned_to_npp, tgl_selesai FROM pekerjaan WHERE master_tugas_id = ? AND tgl_mulai = ? LIMIT 1');
    if (!$stmtE) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
        http_response_code(500);
        exit;
    }
    $stmtE->bind_param('is', $masterId, $occDate);
    $stmtE->execute();
    $resE = $stmtE->get_result();
    $existing = $resE ? $resE->fetch_assoc() : null;
    $stmtE->close();

    if ($existing) {
        if ((string) ($existing['assigned_to_npp'] ?? '') !== $npp) {
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            http_response_code(403);
            exit;
        }
        $id = (int) ($existing['id'] ?? 0);
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
            http_response_code(500);
            exit;
        }
        $existingEnd = trim((string) ($existing['tgl_selesai'] ?? ''));
        if ($existingEnd === '') {
            // Fill tgl_selesai using system baseline duration; if no baseline duration, default to tgl_mulai.
            $durationDays = 0;
            $stmtB = $conn->prepare('SELECT tgl_mulai, tgl_selesai, created_at FROM pekerjaan WHERE master_tugas_id = ? ORDER BY COALESCE(tgl_mulai, DATE(created_at)) ASC, id ASC LIMIT 1');
            if ($stmtB) {
                $stmtB->bind_param('i', $masterId);
                $stmtB->execute();
                $resB = $stmtB->get_result();
                $base = $resB ? $resB->fetch_assoc() : null;
                $stmtB->close();

                if ($base) {
                    $baseStart = $base['tgl_mulai'] ?: null;
                    if (!$baseStart && !empty($base['created_at'])) {
                        $baseStart = date('Y-m-d', strtotime($base['created_at']));
                    }
                    $baseEnd = $base['tgl_selesai'] ?: ($baseStart ?: null);
                    if ($baseStart && $baseEnd) {
                        try {
                            $dStart = new DateTimeImmutable($baseStart);
                            $dEnd = new DateTimeImmutable($baseEnd);
                            $durationDays = (int) $dEnd->diff($dStart)->days;
                            if ($durationDays < 0) $durationDays = 0;
                        } catch (Exception $e) {
                            $durationDays = 0;
                        }
                    }
                }
            }

            $occEndDate = $occDate;
            if ($durationDays > 0) {
                try {
                    $dOccStart = new DateTimeImmutable($occDate);
                    $occEndDate = $dOccStart->modify('+' . $durationDays . ' days')->format('Y-m-d');
                } catch (Exception $e) {
                    $occEndDate = $occDate;
                }
            }

            $up = $conn->prepare("UPDATE pekerjaan SET status = 'done', updated_at = NOW(), tgl_selesai = NULLIF(?, '') WHERE id = ?");
            if (!$up) {
                echo json_encode(['success' => false, 'message' => 'DB error']);
                http_response_code(500);
                exit;
            }
            $up->bind_param('si', $occEndDate, $id);
        } else {
            $up = $conn->prepare("UPDATE pekerjaan SET status = 'done', updated_at = NOW() WHERE id = ?");
            if (!$up) {
                echo json_encode(['success' => false, 'message' => 'DB error']);
                http_response_code(500);
                exit;
            }
            $up->bind_param('i', $id);
        }
        if (!$up) {
            echo json_encode(['success' => false, 'message' => 'DB error']);
            http_response_code(500);
            exit;
        }
        $ok = $up->execute();
        $up->close();

        if ($ok) {
            echo json_encode(['success' => true, 'id' => $id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed']);
            http_response_code(500);
        }
        exit;
    }

    // Determine coordinator identity (for display) and duration (end date) from baseline instance, if any
    $coordinatorNpp = null;
    if ($hasMasterNppManagerCol) {
        $coordinatorNpp = $mt['npp_manager'] ?? null;
    }

    $coordinatorName = 'Manager';
    if (!empty($coordinatorNpp)) {
        $stmtCN = $conn->prepare('SELECT nama_emp FROM employee WHERE npp = ? LIMIT 1');
        if ($stmtCN) {
            $stmtCN->bind_param('s', $coordinatorNpp);
            $stmtCN->execute();
            $resCN = $stmtCN->get_result();
            $rowCN = $resCN ? $resCN->fetch_assoc() : null;
            if (!empty($rowCN['nama_emp'])) $coordinatorName = $rowCN['nama_emp'];
            $stmtCN->close();
        }
    }

    $durationDays = 0;
    $stmtB = $conn->prepare('SELECT tgl_mulai, tgl_selesai, created_at FROM pekerjaan WHERE master_tugas_id = ? ORDER BY COALESCE(tgl_mulai, DATE(created_at)) ASC, id ASC LIMIT 1');
    if ($stmtB) {
        $stmtB->bind_param('i', $masterId);
        $stmtB->execute();
        $resB = $stmtB->get_result();
        $base = $resB ? $resB->fetch_assoc() : null;
        $stmtB->close();

        if ($base) {
            $baseStart = $base['tgl_mulai'] ?: null;
            if (!$baseStart && !empty($base['created_at'])) {
                $baseStart = date('Y-m-d', strtotime($base['created_at']));
            }
            $baseEnd = $base['tgl_selesai'] ?: ($baseStart ?: null);
            if ($baseStart && $baseEnd) {
                try {
                    $dStart = new DateTimeImmutable($baseStart);
                    $dEnd = new DateTimeImmutable($baseEnd);
                    $durationDays = (int) $dEnd->diff($dStart)->days;
                    if ($durationDays < 0) $durationDays = 0;
                } catch (Exception $e) {
                    $durationDays = 0;
                }
            }
        }
    }

    // Always fill tgl_selesai in pekerjaan with system-calculated date.
    // If there is no baseline duration, default to same date as tgl_mulai.
    $occEndDate = $occDate;
    if ($durationDays > 0) {
        try {
            $dOccStart = new DateTimeImmutable($occDate);
            $occEndDate = $dOccStart->modify('+' . $durationDays . ' days')->format('Y-m-d');
        } catch (Exception $e) {
            $occEndDate = $occDate;
        }
    }

    $judul = trim((string) ($mt['judul'] ?? ''));
    $deskripsi = trim((string) ($mt['deskripsi'] ?? ''));
    $periode = trim((string) ($mt['periode'] ?? 'bulanan'));

    // We store coordinator name in pekerjaan.nama_emp so UI consistently shows "Manager (manager)"
    // even if created_by_npp isn't a manager in older schemas.
    $createdBy = !empty($coordinatorNpp) ? (string) $coordinatorNpp : (string) $npp;

    $ins = $conn->prepare("INSERT INTO pekerjaan (judul, deskripsi, created_by_npp, nama_emp, tgl_mulai, tgl_selesai, assigned_to_npp, master_tugas_id, periode, status) VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?, ?, NULLIF(?, ''), 'done')");
    if (!$ins) {
        echo json_encode(['success' => false, 'message' => 'DB error']);
        http_response_code(500);
        exit;
    }
    $ins->bind_param('sssssssis', $judul, $deskripsi, $createdBy, $coordinatorName, $occDate, $occEndDate, $assignedTo, $masterId, $periode);
    $ok = $ins->execute();
    $newId = $ins->insert_id;
    $ins->close();

    if ($ok) {
        echo json_encode(['success' => true, 'id' => $newId]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed']);
        http_response_code(500);
    }
    exit;
}

$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid id']);
    http_response_code(400);
    exit;
}

$stmt = $conn->prepare('SELECT assigned_to_npp FROM pekerjaan WHERE id = ? LIMIT 1');
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    http_response_code(500);
    exit;
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Not found']);
    http_response_code(404);
    exit;
}

$assigned = $row['assigned_to_npp'];
if ($assigned !== $npp) {
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    http_response_code(403);
    exit;
}

$up = $conn->prepare("UPDATE pekerjaan SET status = 'done', updated_at = NOW() WHERE id = ?");
if (!$up) {
    echo json_encode(['success' => false, 'message' => 'DB error']);
    http_response_code(500);
    exit;
}
$up->bind_param('i', $id);
$ok = $up->execute();
$up->close();

if ($ok) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed']);
    http_response_code(500);
}
