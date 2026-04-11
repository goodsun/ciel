<?php
session_start(['cookie_samesite' => 'Lax', 'cookie_httponly' => true]);
$loggedIn = !empty($_SESSION['user']);

$supported = ['ja', 'en', 'zh', 'ko', 'es'];
$lang = $_GET['lang'] ?? '';
if (!in_array($lang, $supported, true)) {
    // Detect from Accept-Language header
    $lang = 'en';
    if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
        $preferred = strtolower(substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2));
        if (in_array($preferred, $supported, true)) {
            $lang = $preferred;
        }
    }
}
$t = require __DIR__ . '/lang/' . $lang . '.php';

// Helper to escape output
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= e($t['html_lang']) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($t['title']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

:root {
  --bg: #06060c;
  --text: #d0d4e0;
  --text-dim: #70748a;
  --accent: #8ba4d4;
  --accent-bright: #a0bef0;
  --serif: 'EB Garamond', Georgia, 'Times New Roman', serif;
  --sans: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Hiragino Sans', sans-serif;
}

html { scroll-behavior: smooth; }

body {
  font-family: var(--sans);
  background: var(--bg);
  color: var(--text);
  overflow-x: hidden;
  -webkit-font-smoothing: antialiased;
}

/* ===== Language Switcher ===== */
.lang-switcher {
  position: fixed;
  top: 1rem;
  right: 1.5rem;
  z-index: 100;
  display: flex;
  gap: 0.5rem;
}

.lang-switcher a {
  font-family: var(--sans);
  font-size: 0.72rem;
  color: var(--text-dim);
  text-decoration: none;
  letter-spacing: 0.04em;
  padding: 4px 8px;
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 3px;
  transition: color 0.3s, border-color 0.3s;
}

.lang-switcher a:hover,
.lang-switcher a.active {
  color: var(--accent-bright);
  border-color: rgba(160,190,240,0.3);
}

/* ===== Section 0: Opening ===== */
.opening {
  height: 100vh;
  min-height: 560px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
}

.opening-slides {
  position: absolute;
  inset: 0;
  z-index: 0;
}

.opening-slide {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
  opacity: 0;
  transition: opacity 1.6s ease;
}

.opening-slide.active { opacity: 1; }

.opening-slide::after {
  content: '';
  position: absolute;
  inset: 0;
  background: radial-gradient(
    ellipse at center,
    rgba(6,6,12,0.55) 0%,
    rgba(6,6,12,0.8) 60%,
    rgba(6,6,12,0.95) 100%
  );
}

.opening-icon {
  position: relative;
  z-index: 1;
  width: 100px;
  height: 100px;
  border-radius: 24px;
  object-fit: cover;
  opacity: 0;
  animation: fadeFloat 2s ease-out 0.3s forwards;
  box-shadow: 0 0 60px rgba(160,190,240,0.12);
}

.opening-title {
  position: relative;
  z-index: 1;
  font-family: var(--serif);
  font-size: clamp(3.6rem, 12vw, 7rem);
  font-weight: 400;
  letter-spacing: 0.2em;
  color: #fff;
  margin-top: 1.6rem;
  opacity: 0;
  animation: fadeUp 1.8s ease-out 0.8s forwards;
}

.opening-lead {
  position: relative;
  z-index: 1;
  font-family: var(--serif);
  font-style: italic;
  font-size: clamp(0.95rem, 2.5vw, 1.2rem);
  color: rgba(200,210,230,0.7);
  margin-top: 0.8rem;
  letter-spacing: 0.06em;
  opacity: 0;
  animation: fadeUp 1.8s ease-out 1.3s forwards;
}

.opening-cta {
  position: relative;
  z-index: 1;
  display: inline-block;
  margin-top: 2.2rem;
  padding: 14px 48px;
  background: transparent;
  border: 1px solid rgba(160,190,240,0.3);
  color: #fff;
  font-family: var(--serif);
  font-size: 1rem;
  letter-spacing: 0.12em;
  text-decoration: none;
  border-radius: 0;
  opacity: 0;
  animation: fadeUp 1.8s ease-out 1.8s forwards;
  transition: background 0.4s, border-color 0.4s;
}

.opening-cta:hover {
  background: rgba(160,190,240,0.08);
  border-color: rgba(160,190,240,0.6);
}

.opening-terms {
  position: relative;
  z-index: 1;
  display: block;
  margin-top: 1rem;
  font-family: var(--sans);
  font-size: 0.75rem;
  color: rgba(200,210,230,0.4);
  text-decoration: none;
  letter-spacing: 0.04em;
  opacity: 0;
  animation: fadeUp 1.8s ease-out 2.2s forwards;
  transition: color 0.3s;
}

.opening-terms:hover {
  color: rgba(200,210,230,0.7);
}

.scroll-hint {
  position: absolute;
  z-index: 1;
  bottom: 3rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  opacity: 0;
  animation: fadeUp 1.5s ease-out 2s forwards;
}

.scroll-hint span {
  font-family: var(--serif);
  font-size: 0.75rem;
  color: var(--text-dim);
  letter-spacing: 0.15em;
}

.scroll-line {
  width: 1px;
  height: 40px;
  background: linear-gradient(to bottom, var(--text-dim), transparent);
  animation: scrollPulse 2s ease-in-out infinite;
}

@keyframes fadeFloat {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes fadeUp {
  from { opacity: 0; transform: translateY(16px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes scrollPulse {
  0%, 100% { opacity: 0.3; }
  50% { opacity: 0.8; }
}

/* ===== Story Sections ===== */
.story {
  position: relative;
  min-height: 100vh;
  display: flex;
  align-items: center;
  overflow: hidden;
}

.story-image {
  position: absolute;
  inset: 0;
  background-size: cover;
  background-position: center;
}

.story--1 .story-image {
  mask-image: linear-gradient(to right, black 40%, transparent 75%);
  -webkit-mask-image: linear-gradient(to right, black 40%, transparent 75%);
  filter: saturate(0.75) brightness(0.8);
}

.story--1 .story-text {
  margin-left: auto;
  margin-right: 8%;
  text-align: right;
}

.story--2 .story-image {
  mask-image: linear-gradient(to left, black 40%, transparent 75%);
  -webkit-mask-image: linear-gradient(to left, black 40%, transparent 75%);
  filter: saturate(0.75) brightness(0.8);
}

.story--2 .story-text {
  margin-left: 8%;
}

.story--3 .story-image {
  opacity: 0.35;
  filter: blur(1px) brightness(0.7);
}

.story--3 .story-text {
  margin: 0 auto;
  text-align: center;
}

.story-text {
  position: relative;
  z-index: 2;
  padding: 2rem;
}

.story-catch {
  font-family: var(--serif);
  font-size: clamp(1.6rem, 4vw, 2.4rem);
  font-weight: 400;
  color: #fff;
  line-height: 1.6;
  letter-spacing: 0.05em;
  margin-bottom: 1rem;
}

.story-body {
  font-size: 0.92rem;
  color: rgba(200,210,230,0.7);
  line-height: 1.9;
}

.story-specs {
  list-style: none;
  margin-top: 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.6rem;
}

.story-specs li {
  font-family: var(--serif);
  font-size: 1rem;
  color: var(--accent);
  letter-spacing: 0.04em;
}

.story-specs li::before {
  content: '\2014\00a0';
  color: var(--text-dim);
}

.story-image--placeholder {
  background: linear-gradient(135deg, #0e0e1a 0%, #161628 50%, #0e0e1a 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}

.story-image--placeholder::before {
  content: attr(data-label);
  font-family: var(--sans);
  font-size: 0.85rem;
  color: rgba(255,255,255,0.12);
  letter-spacing: 0.1em;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}

.story-cta {
  display: inline-block;
  margin-top: 2rem;
  padding: 16px 52px;
  background: transparent;
  border: 1px solid rgba(160,190,240,0.3);
  color: #fff;
  font-family: var(--serif);
  font-size: 1.05rem;
  letter-spacing: 0.12em;
  text-decoration: none;
  border-radius: 0;
  transition: background 0.4s, border-color 0.4s;
}

.story-cta:hover {
  background: rgba(160,190,240,0.08);
  border-color: rgba(160,190,240,0.6);
}

.story-cta-sub {
  display: block;
  margin-top: 0.6rem;
  font-family: var(--sans);
  font-size: 0.75rem;
  color: var(--text-dim);
  letter-spacing: 0.04em;
}

/* ===== How it Works ===== */
.how {
  max-width: 800px;
  margin: 0 auto;
  padding: 8rem 1.5rem;
}

.section-title {
  font-family: var(--serif);
  font-size: clamp(1.4rem, 3vw, 1.8rem);
  font-weight: 400;
  color: #fff;
  text-align: center;
  letter-spacing: 0.1em;
  margin-bottom: 3.5rem;
}

.steps {
  display: flex;
  gap: 2rem;
  justify-content: center;
}

.step {
  flex: 1;
  text-align: center;
  max-width: 220px;
}

.step-num {
  font-family: var(--serif);
  font-size: 2.4rem;
  color: rgba(160,190,240,0.25);
  line-height: 1;
  margin-bottom: 0.8rem;
}

.step h3 {
  font-family: var(--serif);
  font-size: 1.05rem;
  font-weight: 500;
  color: var(--accent-bright);
  margin-bottom: 0.5rem;
}

.step p {
  font-size: 0.82rem;
  color: var(--text-dim);
  line-height: 1.7;
}

.step-connector {
  display: flex;
  align-items: center;
  color: rgba(160,190,240,0.15);
  font-size: 1.4rem;
  padding-top: 1rem;
}

/* ===== Gallery ===== */
.gallery-section {
  padding: 4rem 1.5rem 6rem;
}

.gallery-section .section-title { margin-bottom: 2.5rem; }

.gallery {
  display: flex;
  gap: 1.2rem;
  justify-content: center;
  flex-wrap: wrap;
  max-width: 1000px;
  margin: 0 auto;
}

.gallery figure {
  position: relative;
  width: 300px;
  overflow: hidden;
  border-radius: 4px;
}

.gallery img {
  width: 100%;
  height: auto;
  display: block;
  filter: saturate(0.85);
  transition: filter 0.4s, transform 0.4s;
}

.gallery figure:hover img {
  filter: saturate(1);
  transform: scale(1.02);
}

.gallery figcaption {
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  padding: 1.2rem 0.8rem 0.6rem;
  background: linear-gradient(to top, rgba(6,6,12,0.85), transparent);
  font-family: var(--sans);
  font-size: 0.7rem;
  color: var(--text-dim);
  letter-spacing: 0.03em;
  opacity: 0;
  transition: opacity 0.4s;
}

.gallery figure:hover figcaption { opacity: 1; }

/* ===== FAQ ===== */
.faq {
  max-width: 640px;
  margin: 0 auto;
  padding: 4rem 1.5rem 6rem;
}

.faq details {
  border-bottom: 1px solid rgba(255,255,255,0.06);
  padding: 1.2rem 0;
}

.faq summary {
  font-family: var(--serif);
  font-size: 1rem;
  color: var(--accent-bright);
  cursor: pointer;
  letter-spacing: 0.03em;
  list-style: none;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.faq summary::-webkit-details-marker { display: none; }

.faq summary::after {
  content: '+';
  font-size: 1.2rem;
  color: var(--text-dim);
  transition: transform 0.3s;
}

.faq details[open] summary::after {
  transform: rotate(45deg);
}

.faq .faq-answer {
  padding-top: 0.8rem;
  font-size: 0.88rem;
  color: var(--text-dim);
  line-height: 1.8;
}

/* ===== Final CTA ===== */
.final-cta {
  text-align: center;
  padding: 6rem 1.5rem 8rem;
  position: relative;
}

.final-cta-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  object-fit: cover;
  opacity: 0.6;
  margin-bottom: 1.5rem;
}

.final-cta .story-catch {
  margin-bottom: 1.5rem;
}

/* ===== Footer ===== */
footer {
  text-align: center;
  padding: 2rem 1rem 3rem;
  font-size: 0.78rem;
  color: #444;
  border-top: 1px solid rgba(255,255,255,0.04);
}

footer a {
  color: var(--text-dim);
  text-decoration: none;
}

footer a:hover { color: var(--accent); }

/* ===== Scroll Animations ===== */
.reveal {
  opacity: 0;
  transform: translateY(30px);
  transition: opacity 0.8s ease-out, transform 0.8s ease-out;
}

.reveal.visible {
  opacity: 1;
  transform: translateY(0);
}

/* ===== Mobile ===== */
@media (max-width: 768px) {
  .story { min-height: 80vh; }

  .story--1 .story-image {
    mask-image: linear-gradient(to bottom, black 30%, transparent 70%);
    -webkit-mask-image: linear-gradient(to bottom, black 30%, transparent 70%);
  }
  .story--1 .story-text {
    margin: auto 1.5rem 2rem;
    text-align: left;
  }

  .story--2 .story-image {
    mask-image: linear-gradient(to bottom, black 30%, transparent 70%);
    -webkit-mask-image: linear-gradient(to bottom, black 30%, transparent 70%);
  }
  .story--2 .story-text {
    margin: auto 1.5rem 2rem;
  }

  .story--3 .story-text { padding: 1.5rem; }

  .steps {
    flex-direction: column;
    align-items: center;
  }

  .step-connector { display: none; }

  .gallery figure { width: 85%; }

  .opening-icon { width: 72px; height: 72px; border-radius: 18px; }
}
</style>
</head>
<body>

<!-- Language Switcher -->
<nav class="lang-switcher">
<?php foreach (['ja' => 'JA', 'en' => 'EN', 'zh' => 'ZH', 'ko' => 'KO', 'es' => 'ES'] as $code => $label): ?>
  <a href="?lang=<?= $code ?>"<?= $lang === $code ? ' class="active"' : '' ?>><?= $label ?></a>
<?php endforeach; ?>
</nav>

<!-- Section 0: Opening -->
<section class="opening">
  <div class="opening-slides">
    <div class="opening-slide active" style="background-image:url('img/hero1.jpg')"></div>
    <div class="opening-slide" style="background-image:url('img/hero2.jpg')"></div>
    <div class="opening-slide" style="background-image:url('img/hero3.jpg')"></div>
  </div>
  <img src="img/icon.jpg" alt="le ciel" class="opening-icon">
  <h1 class="opening-title">le ciel</h1>
  <p class="opening-lead"><?= e($t['lead']) ?></p>
<?php if ($loggedIn): ?>
  <a href="image.php" class="opening-cta"><?= e($t['hero_cta']) ?></a>
<?php else: ?>
  <a href="login.php" class="opening-cta"><?= e($t['hero_cta_login']) ?></a>
<?php endif; ?>
  <a href="service.php?lang=<?= $lang ?>" class="opening-terms"><?= e($t['terms']) ?></a>
  <div class="scroll-hint">
    <span><?= e($t['scroll']) ?></span>
    <div class="scroll-line"></div>
  </div>
</section>

<!-- Section 1: Imagine -->
<section class="story story--1">
  <div class="story-image" style="background-image:url('img/dummy1.jpg')"></div>
  <div class="story-text reveal">
    <p class="story-catch"><?= e($t['story1_catch']) ?></p>
    <p class="story-body"><?= $t['story1_body'] ?></p>
  </div>
</section>

<!-- Section 2: Why le ciel -->
<section class="story story--2">
  <div class="story-image" style="background-image:url('img/dummy2.jpg')"></div>
  <div class="story-text reveal">
    <p class="story-catch"><?= e($t['story2_catch']) ?></p>
    <ul class="story-specs">
<?php foreach ($t['story2_specs'] as $spec): ?>
      <li><?= e($spec) ?></li>
<?php endforeach; ?>
    </ul>
  </div>
</section>

<!-- Section 3: CTA -->
<section class="story story--3">
  <div class="story-image" style="background-image:url('img/dummy3.jpg')"></div>
  <div class="story-text reveal">
    <p class="story-catch"><?= e($t['story3_catch']) ?></p>
    <a href="top.php" class="story-cta"><?= e($t['cta']) ?></a>
    <span class="story-cta-sub"><?= e($t['cta_sub']) ?></span>
  </div>
</section>

<!-- How it Works -->
<section class="how">
  <h2 class="section-title reveal"><?= e($t['how_title']) ?></h2>
  <div class="steps reveal">
    <div class="step">
      <div class="step-num">1</div>
      <h3><?= e($t['step1_title']) ?></h3>
      <p><?= e($t['step1_desc']) ?></p>
    </div>
    <div class="step-connector">&rarr;</div>
    <div class="step">
      <div class="step-num">2</div>
      <h3><?= e($t['step2_title']) ?></h3>
      <p><?= e($t['step2_desc']) ?></p>
    </div>
    <div class="step-connector">&rarr;</div>
    <div class="step">
      <div class="step-num">3</div>
      <h3><?= e($t['step3_title']) ?></h3>
      <p><?= e($t['step3_desc']) ?></p>
    </div>
  </div>
</section>

<!-- Gallery -->
<section class="gallery-section">
  <h2 class="section-title reveal"><?= e($t['gallery_title']) ?></h2>
  <div class="gallery reveal">
<?php for ($i = 0; $i < 6; $i++): ?>
    <figure>
      <img src="img/dummy<?= $i + 1 ?>.jpg" alt="Generated sample <?= $i + 1 ?>">
      <figcaption><?= e($t['gallery_captions'][$i]) ?></figcaption>
    </figure>
<?php endfor; ?>
  </div>
</section>

<!-- FAQ -->
<section class="faq">
  <h2 class="section-title reveal"><?= e($t['faq_title']) ?></h2>
<?php foreach ($t['faq'] as $item): ?>
  <details class="reveal">
    <summary><?= e($item['q']) ?></summary>
    <div class="faq-answer"><?= str_replace('href="service.php"', 'href="service.php?lang=' . $lang . '"', $item['a']) ?></div>
  </details>
<?php endforeach; ?>
</section>

<!-- Final CTA -->
<section class="final-cta reveal">
  <img src="img/icon.jpg" alt="" class="final-cta-icon">
  <p class="story-catch"><?= e($t['final_catch']) ?></p>
  <a href="top.php" class="story-cta"><?= e($t['cta']) ?></a>
  <span class="story-cta-sub"><?= e($t['cta_sub']) ?></span>
</section>

<!-- Donate -->
<section class="donate reveal" style="max-width:480px;margin:0 auto;padding:3rem 1.5rem 5rem;text-align:center;">
  <p style="font-size:0.85rem;color:var(--text-dim);line-height:1.9;margin-bottom:1.2rem;"><?= e($t['donate_message']) ?></p>
  <div style="font-family:var(--sans);font-size:0.7rem;color:rgba(255,255,255,0.2);letter-spacing:0.03em;">
    <span style="color:var(--text-dim);">ETH</span>
    <code style="display:block;margin-top:4px;font-size:0.68rem;color:rgba(255,255,255,0.15);word-break:break-all;letter-spacing:0.02em;">0xB55D25fBE0030b346589C7Dc00E02F82143B0f0b</code>
  </div>
</section>

<!-- Footer -->
<footer>
  <p>&copy; 2026 <a href="https://bon-soleil.com">bonsoleil</a> &mdash; <a href="service.php?lang=<?= $lang ?>"><?= e($t['terms']) ?></a></p>
</footer>

<!-- Slideshow + Scroll Reveal -->
<script>
(function() {
  var slides = document.querySelectorAll('.opening-slide');
  var current = 0;
  setInterval(function() {
    slides[current].classList.remove('active');
    current = (current + 1) % slides.length;
    slides[current].classList.add('active');
  }, 4500);

  var els = document.querySelectorAll('.reveal');
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  els.forEach(function(el) { observer.observe(el); });
})();
</script>

</body>
</html>
