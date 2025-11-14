<?php
// pro_calendar.php
require_once __DIR__.'/layout.php';
require_once __DIR__.'/guard.php';

require_profesional(); // Solo profesionales logueados
$user  = current_user();
$proId = (int)($user['id'] ?? 0);

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function wstart_monday(DateTime $d): DateTime {
    // Lunes como comienzo de semana
    $clone = (clone $d);
    $w = (int)$clone->format('N'); // 1..7 (1=Lunes)
    if ($w > 1) $clone->modify('-'.($w-1).' days');
    return $clone->setTime(0,0,0);
}
function fmt_ampm_es(string $timeHHMM): string {
    // formatea "HH:MM:SS" o "HH:MM" a "h:mm a.m./p.m."
    $parts = explode(':', $timeHHMM);
    $h = (int)($parts[0] ?? 0);
    $m = (int)($parts[1] ?? 0);
    $ampm = ($h < 12) ? 'a.m.' : 'p.m.';
    $h12 = $h % 12; if ($h12 === 0) $h12 = 12;
    return sprintf('%d:%02d %s', $h12, $m, $ampm);
}

// ============================
//  Esquema BD (si hay BD)
// ============================
$useDb = function_exists('db_ready') && db_ready();
if ($useDb) {
    try {
        pdo()->exec("
            CREATE TABLE IF NOT EXISTS pro_slots (
                id INT AUTO_INCREMENT PRIMARY KEY,
                pro_id INT NOT NULL,
                slot_date DATE NOT NULL,
                slot_time TIME NOT NULL,
                is_booked TINYINT(1) NOT NULL DEFAULT 0,
                booked_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_slot (pro_id, slot_date, slot_time),
                INDEX (pro_id, slot_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (Throwable $e) { $useDb = false; }
}

// Fallback SESSION
$SESSION_KEY = 'pro_slots';
if (!$useDb && !isset($_SESSION[$SESSION_KEY])) $_SESSION[$SESSION_KEY] = []; // [proKey=>[...]]
$PRO_KEY = $proId > 0 ? 'id:'.$proId : 'email:'.($user['email'] ?? '');

// ============================
//  Parámetros de vista (semana)
// ============================
$baseDate = isset($_GET['date']) ? DateTime::createFromFormat('Y-m-d', $_GET['date']) : null;
if (!$baseDate) $baseDate = new DateTime('today');
$weekStart = wstart_monday($baseDate);
$weekEnd   = (clone $weekStart)->modify('+6 days');

// Navegación semanas
$prevWeek = (clone $weekStart)->modify('-7 days')->format('Y-m-d');
$nextWeek = (clone $weekStart)->modify('+7 days')->format('Y-m-d');

// ============================
//  Acciones (generar / eliminar / liberar)
// ============================
$notice = null; $error = null;

// Generar disponibilidad
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__generate'])) {
    $from = trim($_POST['from'] ?? '');
    $to   = trim($_POST['to'] ?? '');
    $step = max(5, (int)($_POST['step'] ?? 30)); // minutos
    $weeksAhead = max(1, min(8, (int)($_POST['weeks'] ?? 1)));
    $days = $_POST['days'] ?? []; // ['1','2',... '7'] (1=Lun..7=Dom)

    // Validación hora
    $okFrom = preg_match('~^\d{2}:\d{2}$~', $from);
    $okTo   = preg_match('~^\d{2}:\d{2}$~', $to);
    if (!$okFrom || !$okTo || !$days) {
        $error = 'Completa horas válidas (HH:MM) y selecciona al menos un día.';
    } else {
        try {
            // Generar para cada semana (desde la semana visible)
            for ($w=0; $w<$weeksAhead; $w++) {
                $start = (clone $weekStart)->modify("+{$w} week");
                for ($i=0; $i<7; $i++) {
                    $d = (clone $start)->modify("+{$i} day");
                    $weekday = (int)$d->format('N'); // 1..7
                    if (!in_array((string)$weekday, $days, true)) continue;

                    // De from a to, cada $step minutos
                    [$fh, $fm] = array_map('intval', explode(':', $from));
                    [$th, $tm] = array_map('intval', explode(':', $to));
                    $cur = (clone $d)->setTime($fh,$fm,0);
                    $end = (clone $d)->setTime($th,$tm,0);
                    if ($cur >= $end) continue;

                    while ($cur < $end) {
                        $dateStr = $cur->format('Y-m-d');
                        $timeStr = $cur->format('H:i:00');

                        if ($useDb) {
                            $ins = pdo()->prepare("INSERT IGNORE INTO pro_slots (pro_id, slot_date, slot_time) VALUES (?,?,?)");
                            $ins->execute([$proId, $dateStr, $timeStr]);
                        } else {
                            if (!isset($_SESSION[$SESSION_KEY][$PRO_KEY])) $_SESSION[$SESSION_KEY][$PRO_KEY] = [];
                            $exists = false;
                            foreach ($_SESSION[$SESSION_KEY][$PRO_KEY] as $sl) {
                                if (($sl['slot_date']??'')===$dateStr && ($sl['slot_time']??'')===$timeStr) { $exists = true; break; }
                            }
                            if (!$exists) {
                                $_SESSION[$SESSION_KEY][$PRO_KEY][] = [
                                    'id' => time().rand(100,999),
                                    'pro_id'=>$proId,
                                    'slot_date'=>$dateStr,
                                    'slot_time'=>$timeStr,
                                    'is_booked'=>0,
                                    'booked_by'=>null,
                                    'created_at'=>date('Y-m-d H:i:s'),
                                ];
                            }
                        }
                        $cur->modify("+{$step} minutes");
                    }
                }
            }
            $notice = 'Disponibilidad generada correctamente.';
        } catch (Throwable $e) {
            $error = 'No se pudo generar la disponibilidad.';
        }
    }
}

// Eliminar slot
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    try {
        if ($useDb) {
            pdo()->prepare("DELETE FROM pro_slots WHERE id=? AND pro_id=? AND is_booked=0")->execute([$id, $proId]);
        } else {
            $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
            $new  = [];
            foreach ($list as $sl) {
                if ((int)($sl['id']??0) === $id && (int)($sl['is_booked']??0) === 0) continue;
                $new[] = $sl;
            }
            $_SESSION[$SESSION_KEY][$PRO_KEY] = $new;
        }
        $notice = 'Slot eliminado (si no estaba reservado).';
    } catch (Throwable $e) {
        $error = 'No se pudo eliminar el slot.';
    }
}

// Liberar (quitar marca de reservado)
if (isset($_GET['free'])) {
    $id = (int)$_GET['free'];
    try {
        if ($useDb) {
            pdo()->prepare("UPDATE pro_slots SET is_booked=0, booked_by=NULL WHERE id=? AND pro_id=?")
                ->execute([$id, $proId]);
        } else {
            $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
            foreach ($list as &$sl) {
                if ((int)($sl['id']??0) === $id) { $sl['is_booked']=0; $sl['booked_by']=null; break; }
            }
            $_SESSION[$SESSION_KEY][$PRO_KEY] = $list;
        }
        $notice = 'Slot liberado.';
    } catch (Throwable $e) { $error = 'No se pudo liberar el slot.'; }
}

// ============================
//  Cargar slots de la semana
// ============================
$slotsByDate = []; // 'Y-m-d' => [rows...]
try {
    if ($useDb) {
        $q = pdo()->prepare("SELECT * FROM pro_slots WHERE pro_id=? AND slot_date BETWEEN ? AND ? ORDER BY slot_date, slot_time");
        $q->execute([$proId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d')]);
        foreach ($q as $row) {
            $day = $row['slot_date'];
            if (!isset($slotsByDate[$day])) $slotsByDate[$day] = [];
            $slotsByDate[$day][] = $row;
        }
    } else {
        $list = $_SESSION[$SESSION_KEY][$PRO_KEY] ?? [];
        foreach ($list as $sl) {
            $d = $sl['slot_date'] ?? '';
            if ($d >= $weekStart->format('Y-m-d') && $d <= $weekEnd->format('Y-m-d')) {
                if (!isset($slotsByDate[$d])) $slotsByDate[$d] = [];
                $slotsByDate[$d][] = $sl;
            }
        }
        foreach ($slotsByDate as &$arr) {
            usort($arr, fn($a,$b)=> strcmp(($a['slot_time']??''), ($b['slot_time']??'')));
        }
    }
} catch (Throwable $e) {
    $slotsByDate = [];
}

// ============================
//  Render
// ============================
render_header('Calendario del profesional');
?>
<div class="home-hero">
  <h2>Mi calendario (disponibilidad)</h2>
  <p style="margin:6px 0 14px;color:#556">Organiza tus horarios por semana. Todo está centrado y con formato <em>a.m./p.m.</em></p>

  <?php if ($notice): ?><div class="alert success" style="max-width:900px;margin:0 auto"><?= h($notice) ?></div><?php endif; ?>
  <?php if ($error):  ?><div class="alert danger"  style="max-width:900px;margin:0 auto"><?= h($error)  ?></div><?php endif; ?>

  <p style="margin:10px 0;">
    <a class="btn outline" href="index.php?r=pro_dashboard">Volver al panel</a>
  </p>

  <!-- Navegación de semana -->
  <section class="card" style="max-width:980px;text-align:center">
    <div style="display:flex;gap:10px;justify-content:center;align-items:center;flex-wrap:wrap">
      <a class="btn" href="index.php?r=pro_calendar&date=<?= h($prevWeek) ?>">← Semana anterior</a>
      <strong><?= $weekStart->format('d/m/Y') ?> — <?= $weekEnd->format('d/m/Y') ?></strong>
      <a class="btn" href="index.php?r=pro_calendar&date=<?= h($nextWeek) ?>">Semana siguiente →</a>
    </div>
  </section>

  <!-- Generador de disponibilidad -->
  <section class="card pay-wrap" style="text-align:center;margin-top:12px">
    <h3 style="margin-top:0">Generar disponibilidad</h3>
    <form method="post" class="auth-form" style="align-items:center">
      <input type="hidden" name="__generate" value="1">
      <div class="form-row">
        <label>Desde (HH:MM)
          <input type="time" name="from" required value="08:00">
        </label>
        <label>Hasta (HH:MM)
          <input type="time" name="to" required value="18:00">
        </label>
        <label>Intervalo (min)
          <select name="step" required>
            <?php foreach ([15,20,30,45,60] as $m): ?>
              <option value="<?= $m ?>" <?= $m===30?'selected':'' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Semanas a generar
          <select name="weeks">
            <?php for($i=1;$i<=4;$i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </label>
      </div>

      <div class="form-row" style="justify-content:center">
        <?php
          $dayNames = [1=>'Lunes',2=>'Martes',3=>'Miércoles',4=>'Jueves',5=>'Viernes',6=>'Sábado',7=>'Domingo'];
          foreach ($dayNames as $n=>$label):
        ?>
          <label style="flex:0 1 auto">
            <input type="checkbox" name="days[]" value="<?= $n ?>" <?= $n<=5?'checked':'' ?>> <?= $label ?>
          </label>
        <?php endforeach; ?>
      </div>

      <button class="btn primary" type="submit" style="margin-top:6px">Generar</button>
      <p style="color:#777;font-size:12px;margin:6px 0">
        Se generan slots desde la semana visible (<?= $weekStart->format('d/m') ?>) por la cantidad de semanas elegida.
      </p>
    </form>
  </section>

  <!-- Calendario semanal -->
  <section class="card" style="max-width:1024px;text-align:center;margin-top:12px">
    <h3 style="margin-top:0">Semana actual</h3>

    <div class="pro-grid" style="grid-template-columns:repeat(1,1fr);max-width:980px">
      <?php for ($i=0;$i<7;$i++):
        $d = (clone $weekStart)->modify("+{$i} day");
        $dayKey = $d->format('Y-m-d');
        $niceDay = ucfirst(strftime('%A %d/%m', $d->getTimestamp()));
        if ($niceDay === '' || $niceDay === false) { // fallback si locale
          $niceDay = $d->format('D d/m');
        }
        $list = $slotsByDate[$dayKey] ?? [];
      ?>
        <div class="pro-card">
          <h4 style="margin:0 0 8px 0"><?= h($niceDay) ?></h4>
          <?php if (!$list): ?>
            <p style="color:#777;margin:6px 0">Sin disponibilidad.</p>
          <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:center">
              <?php foreach ($list as $sl):
                $id   = (int)($sl['id'] ?? 0);
                $tm   = (string)($sl['slot_time'] ?? '');
                $book = (int)($sl['is_booked'] ?? 0) === 1;
                $label= fmt_ampm_es($tm);
              ?>
                <span class="slot <?= $book?'booked':'' ?>" style="display:inline-flex;align-items:center;gap:6px">
                  <?= h($label) ?>
                  <?php if ($book): ?>
                    <a class="btn outline" style="padding:2px 6px;font-size:12px" 
                       href="index.php?r=pro_calendar&date=<?= h($weekStart->format('Y-m-d')) ?>&free=<?= $id ?>"
                       title="Liberar">Liberar</a>
                  <?php else: ?>
                    <a class="btn outline" style="padding:2px 6px;font-size:12px;color:#a33;border-color:#a33"
                       href="index.php?r=pro_calendar&date=<?= h($weekStart->format('Y-m-d')) ?>&del=<?= $id ?>"
                       onclick="return confirm('¿Eliminar este slot?')"
                       title="Eliminar">Eliminar</a>
                  <?php endif; ?>
                </span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endfor; ?>
    </div>
    <p style="margin-top:12px;color:#777;font-size:12px">
      Los slots “reservados” no pueden eliminarse; primero debes <strong>Liberar</strong>.
    </p>
  </section>
</div>
<?php render_footer();
