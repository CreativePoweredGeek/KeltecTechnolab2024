<h1>Testing Tool</h1>
<div class="panel">
    <div class="panel-body">
        <div class="tab-wrap js-active-tab-group">
            <div class="tab-bar tab-bar--sticky">
                <div class="tab-bar__tabs">
                    <button type="button" class="tab-bar__tab js-tab-button <?php if(!$show_response):?>active<?php endif?>" rel="t-0">XMLRPC</button>
                    <button type="button" class="tab-bar__tab js-tab-button" rel="t-1">SOAP</button>
                    <button type="button" class="tab-bar__tab js-tab-button" rel="t-2">REST</button>
                    <button type="button" class="tab-bar__tab js-tab-button" rel="t-3">Custom</button>
                    <?php if($show_response):?>
                        <button type="button" class="tab-bar__tab js-tab-button highlight active" rel="t-4">Response</button>
                        <button type="button" class="tab-bar__tab js-tab-button highlight" rel="t-5">Request</button>
                    <?php endif;?>
                </div>
            </div>

            <div class="tab t-0 <?php if(!$show_response ):?>tab-open<?php endif;?>">
                <?=$xmplrpc?>
            </div>
            <div class="tab t-1">
                <?=$soap?>
            </div>
            <div class="tab t-2">
                <?=$rest?>
            </div>
            <div class="tab t-3">
                <?=$custom?>
            </div>
            <?php if($show_response):?>
                <div class="tab t-4 tab-open">
                <pre style="max-height: 400px;padding:20px; overflow: scroll">
                    <h3><?=$response['service']?> response</h3><strong><?=$response['url']?></strong>
    <?=print_r($response['response'])?>
                </pre>
                </div>
                <div class="tab t-5">
                <pre style="max-height: 400px;padding:20px; overflow: scroll">
                <?=print_r($response['request'])?>
                </pre>
                </div>
            <?php endif;?>
        </div>
    </div>
</div>
