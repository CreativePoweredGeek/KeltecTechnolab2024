<div class="box">
    <h1>Choose a method to test</h1>
    <div class="txt-wrap">
        <?php foreach($methods as $method):?>
            <a class="btn" href="<?=$method['url']?>" class="less_important_bttn"><?=$method['name']?></a>
        <?php endforeach;?>
    </div>
</div>