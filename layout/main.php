<?php
/**
 * @var Dashboard $this
 */
use Dnolbon\AffiliateImporter\Pages\Dashboard;

$imagePath = plugins_url('assets/img/main', AEIDN_FILE_FULLNAME);

$colors = [
    'yellow',
    'silver',
    'orange',
    'pink'
]
?>
<div class="affiliate-reports">
    <div class="wrap light-tabs" default-rel="backup_settings">
    </div>
    <div class="tab_content" rel="backup_settings">
        <div class="separator"></div>
        <div class="text_content" style="margin-top: 15px">
            <span class="report_data title">
                Statistics for
                <select id="stats" onchange="buildLink()">
                    <option value="1 day"<?= ($this->getStats() === '1 day' ? ' selected' : '') ?>>Day</option>
                    <option value="3 day"<?= ($this->getStats() === '3 day' ? ' selected' : '') ?>>3 Days</option>
                    <option value="7 day"<?= ($this->getStats() === '7 day' ? ' selected' : '') ?>>7 days</option>
                    <option value="30 day"<?= ($this->getStats() === '30 day' ? ' selected' : '') ?>>30 days</option>
                    <option value="60 day"<?= ($this->getStats() === '60 day' ? ' selected' : '') ?>>60 days</option>
                    <option value="90 day"<?= ($this->getStats() === '90 day' ? ' selected' : '') ?>>90 days</option>
                </select>
            </span>
            <?php
            $totals = $this->getTotals();
            ?>
            <span class="report_data">
                <span class="fl-left">
                    <img src="<?= $imagePath . '/items.png' ?>">
                </span>
                <span class="fl-left">
                    <span class="report_number"><?= $this->getTotalNumberProducts() ?></span>
                    <span class="report_title">Total Number of products</span>
                </span>
            </span>
            <span class="report_data">
                <span class="fl-left">
                <img src="<?= $imagePath . '/views.png' ?>">
                    </span>
                    <span class="fl-left">
                <span class="report_number"><?=$totals[0]->hits?></span>
                <span class="report_title">Total products views</span>
                        </span>
            </span>
            <span class="report_data">
                <span class="fl-left">
                <img src="<?= $imagePath . '/litnk.png' ?>">
                    </span>
                    <span class="fl-left">
                <span class="report_number"><?=$totals[0]->orders?></span>
                <span class="report_title">Total redirects</span>
                </span>
            </span>
            <div style="clear: both"></div>
        </div>
        <div class="separator"></div>
        <div class="text_content" style="margin-top: 15px; margin-bottom: 15px;">
            <span class="report_data title">
                Top
                <select id="limit" onchange="buildLink()">
                    <option value="10"<?= ($this->getLimit() === 10 ? ' selected' : '') ?>>10</option>
                    <option value="15"<?= ($this->getLimit() === 15 ? ' selected' : '') ?>>15</option>
                    <option value="30"<?= ($this->getLimit() === 30 ? ' selected' : '') ?>>30</option>
                    <option value="50"<?= ($this->getLimit() === 50 ? ' selected' : '') ?>>50</option>
                    <option value="100"<?= ($this->getLimit() === 100 ? ' selected' : '') ?>>100</option>
                </select>
                Importerd products
            </span>
            <div style="clear: both"></div>
        </div>
        <div class="separator"></div>
        <?php
        $products = $this->getProductsTop();
        ?>
        <div class="text_content" style="margin-top: 15px; margin-bottom: 15px;">
            <?php
            foreach ($products as $i => $product) {
                ?>
                <div class="product_block">
                    <span class="number <?= ($colors[$i % 4]) ?>"># <?= ($i + 1) ?></span>
                    <span class="image">
                        <img src="<?= $product->image ?>">
                    </span>
                    <span class="info">
                        <br>
                    Views: <?= $product->hits ?>
                        <div class="separator"></div>
                    Redirect to: <?= $product->orders ?>
                        <div class="separator"></div>
                </span>
                    <div style="clear: both"></div>
                </div>
                <?php
            }
            ?>
            <div style="clear: both"></div>
        </div>
    </div>
</div>
<script type="text/javascript">
    function buildLink() {
        location.href = 'admin.php?page=<?=$type?>&limit=' + jQuery('#limit').val() + '&stats=' + jQuery('#stats').val();
    }
</script>