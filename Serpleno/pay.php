<?php
// pay.php — método de pago con Tarjeta o Mercado Pago (links específicos por plan y ciclo)
require_once __DIR__.'/layout.php';
require_once __DIR__.'/auth.php';

$user = current_user();
if (!$user) { header('Location: index.php?r=login'); exit; }

// Alias legacy: “estudiantil” => “silver”
function alias_plan(string $p): string {
    $p = strtolower(trim($p));
    return $p === 'estudiantil' ? 'silver' : $p;
}

$planParam = isset($_GET['plan']) ? alias_plan($_GET['plan']) : alias_plan($user['plan'] ?? '');
$validPlans = ['silver','premium'];
if (!in_array($planParam, $validPlans, true)) {
    header('Location: index.php?r=plans');
    exit;
}

// Mostrar precios (los de tu app)
function money_co($n){ return '$'.number_format((int)$n, 0, ',', '.'); }
$PRICES = [
    'silver'  => ['mensual'=>137000, 'semestral'=>675000,  'anual'=>1250000],
    'premium' => ['mensual'=>210000, 'semestral'=>1090000, 'anual'=>2085000],
];
$NAMES = ['silver'=>'Plan Silver', 'premium'=>'Plan Premium'];

// Links de Mercado Pago por plan + periodicidad (los que enviaste)
$MP_LINKS = [
    'silver' => [
        'mensual'   => 'https://mpago.li/1BixZfn',  // mes silver
        'semestral' => 'https://mpago.li/1Vz8ec9',  // semestre silver
        'anual'     => 'https://mpago.li/313J4K2',  // año silver
    ],
    'premium' => [
        'mensual'   => 'https://mpago.li/17hcS3p',  // mes premium
        'semestral' => 'https://mpago.li/22nhqpS',  // semestre premium
        'anual'     => 'https://mpago.li/2vFJJVA',  // año premium
    ],
];

// Procesar pago
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['__pay'])) {
    $cycle  = $_POST['billing_cycle'] ?? 'mensual';
    $method = $_POST['payment_method'] ?? 'tarjeta';

    if (!in_array($cycle, ['mensual','semestral','anual'], true)) $cycle = 'mensual';
    if (!in_array($method, ['tarjeta','mercadopago'], true)) $method = 'tarjeta';

    // Si es Mercado Pago: redirigimos al link exacto según plan+cycle
    if ($method === 'mercadopago') {
        $link = $MP_LINKS[$planParam][$cycle] ?? null;
        if ($link) {
            header('Location: '.$link);
            exit;
        } else {
            render_header('Método de pago');
            echo '<div class="home-hero"><div class="card pay-wrap"><div class="alert danger">No se encontró el enlace de Mercado Pago para la opción seleccionada.</div><p><a class="btn" href="index.php?r=pay&plan='.htmlspecialchars($planParam).'">Volver</a></p></div></div>';
            render_footer();
            exit;
        }
    }

    // Si es tarjeta (flujo demo interno)
    $card_name = trim($_POST['card_name'] ?? '');
    $card_num  = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $exp       = trim($_POST['card_exp'] ?? '');
    $cvv       = trim($_POST['card_cvv'] ?? '');
    if ($card_name === '' || strlen($card_num) < 13 || !preg_match('/^\d{2}\/\d{2}$/', $exp) || strlen($cvv) < 3) {
        render_header('Método de pago');
        echo '<div class="home-hero"><div class="card pay-wrap"><div class="alert danger">Datos de tarjeta incompletos o inválidos.</div><p><a class="btn" href="index.php?r=pay&plan='.htmlspecialchars($planParam).'">Volver</a></p></div></div>';
        render_footer();
        exit;
    }

    // Calcular próxima renovación (demo)
    $next = new DateTime('now');
    if ($cycle === 'mensual')   $next->modify('+1 month');
    if ($cycle === 'semestral') $next->modify('+6 months');
    if ($cycle === 'anual')     $next->modify('+1 year');

    // Activar plan en sesión (y BD si existe)
    set_user_plan($planParam);

    // Notificación de cobro + próxima renovación
    $_SESSION['notifs'][] = [
        'type'  => 'billing',
        'title' => 'Pago confirmado',
        'text'  => 'Plan '.ucfirst($planParam).' ('.$cycle.') — próxima renovación: '.$next->format('Y-m-d'),
        'at'    => date('Y-m-d H:i:s'),
    ];

    // Redirigir a pay_result con resumen
    $amount = $PRICES[$planParam][$cycle] ?? 0;
    $qs = http_build_query([
        'plan'   => $planParam,
        'cycle'  => $cycle,
        'amt'    => $amount,
        'next'   => $next->format('Y-m-d'),
        'method' => $method,
        'ok'     => 1,
    ]);
    header('Location: index.php?r=pay_result&'.$qs);
    exit;
}

render_header('Método de pago');
?>
<div class="home-hero">
  <h2>Método de pago — <?= htmlspecialchars($NAMES[$planParam]) ?></h2>
  <p style="color:#556;margin:6px 0 14px">Elige periodicidad y forma de pago.</p>

  <section class="card pay-wrap" style="text-align:center;">
    <form method="post" class="auth-form" style="align-items:center;">
      <input type="hidden" name="__pay" value="1">

      <div class="grid" style="grid-template-columns:repeat(1,minmax(240px,280px));gap:10px;justify-content:center;">
        <label style="text-align:left;width:100%;max-width:280px;">
          <span style="font-weight:600;">Plan</span>
          <input type="text" readonly value="<?= htmlspecialchars($NAMES[$planParam]) ?>">
        </label>

        <label style="text-align:left;width:100%;max-width:280px;">
          <span style="font-weight:600;">Periodicidad</span>
          <select name="billing_cycle" required>
            <option value="mensual">Mensual (<?= money_co($PRICES[$planParam]['mensual']) ?>)</option>
            <option value="semestral">Semestral (<?= money_co($PRICES[$planParam]['semestral']) ?>)</option>
            <option value="anual">Anual (<?= money_co($PRICES[$planParam]['anual']) ?>)</option>
          </select>
        </label>

        <fieldset style="border:1px solid #eee;border-radius:8px;padding:10px;max-width:360px;">
          <legend style="padding:0 6px;">Método de pago</legend>

          <!-- Tarjeta (demo interno) -->
          <label style="display:flex;gap:6px;align-items:center;margin-bottom:6px;justify-content:center;">
            <input type="radio" name="payment_method" value="tarjeta" checked> Tarjeta (crédito/débito)
          </label>
          <div id="cardFields">
            <label style="text-align:left;max-width:320px;width:100%;">Nombre en la tarjeta
              <input type="text" name="card_name" autocomplete="cc-name">
            </label>
            <label style="text-align:left;max-width:320px;width:100%;">Número de tarjeta
              <input type="text" name="card_number" inputmode="numeric" autocomplete="cc-number" placeholder="4111 1111 1111 1111">
            </label>
            <div class="form-row" style="justify-content:center;">
              <label style="text-align:left;max-width:155px;width:100%;">Vencimiento (MM/AA)
                <input type="text" name="card_exp" autocomplete="cc-exp" placeholder="12/28">
              </label>
              <label style="text-align:left;max-width:155px;width:100%;">CVV
                <input type="text" name="card_cvv" autocomplete="cc-csc" placeholder="123">
              </label>
            </div>
          </div>

          <!-- Mercado Pago (redirige a enlaces reales por ciclo) -->
          <label style="display:flex;gap:6px;align-items:center;justify-content:center;margin-top:8px;">
            <input type="radio" name="payment_method" value="mercadopago"> Mercado Pago
          </label>
          <div id="mpHint" style="display:none;color:#667;font-size:13px;margin-top:6px;">
            Serás redirigido al checkout de Mercado Pago según la periodicidad seleccionada.
          </div>
        </fieldset>
      </div>

      <div style="margin-top:12px;">
        <button class="btn primary" type="submit">Pagar ahora</button>
        <a class="btn" href="index.php?r=plan_detail&plan=<?= urlencode($planParam) ?>" style="margin-left:8px;">Volver</a>
      </div>

      <p style="color:#667;font-size:12px;margin-top:10px;">
        * Nota: con Mercado Pago la activación automática del plan requiere integración de retorno/webhook.
      </p>
    </form>
  </section>
</div>

<script>
(function(){
  const radios = document.querySelectorAll('input[name="payment_method"]');
  const card   = document.getElementById('cardFields');
  const mpHint = document.getElementById('mpHint');
  function sync(){
    const val = document.querySelector('input[name="payment_method"]:checked')?.value || 'tarjeta';
    card.style.display  = val === 'tarjeta' ? '' : 'none';
    mpHint.style.display= val === 'mercadopago' ? '' : 'none';
  }
  radios.forEach(r => r.addEventListener('change', sync));
  sync();
})();
</script>

<?php render_footer(); ?>