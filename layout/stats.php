<?php
use Dnolbon\AffiliateImporter\Pages\Stats;

/**
 * @var Stats $this
 */
?>
<div class="wrap"><h2 class="nav-tab-wrapper"></h2></div>
<div class="wrap light-tabs" default-rel="downloaded">
    <h2 class="nav-tab-wrapper">
        <a href="#" class="nav-tab nav-tab-active" rel="downloaded">Downloaded</a>
    </h2>
    <div class="tab_content affiliate-goods-table" rel="downloaded">
        <div class="separator"></div>
        <?php
        $this->getTable()->display();
        ?>
        <div class="separator"></div>
    </div>
    <div class="tab_content" rel="blacklist">
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function () {
        DnolbonColumns.init('stats');
    });
</script>
