<?php
/** @var \App\Core\View $this */
?>
<h1>Page B</h1>
<p class="muted">Get the installer for your computer.</p>

<section class="panel">
    <form method="post" action="/page-b/download">
        <?= $this->csrfField() ?>
        <button type="submit" class="btn btn-primary btn-large">Download</button>
    </form>
</section>
