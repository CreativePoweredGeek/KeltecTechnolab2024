<div id="super-export-modal-<?= $id; ?>" class="modal-wrap hidden">
	<div class="modal">
		<div class="col-group snap">
			<div class="col w-16 last">
				<a class="m-close-custom" href="Javascript:void(0);"></a>
				<div class="form-standard">
					<div class="form-btns form-btns-top">
						<h3 class="text--left mb-10"><?= $title; ?></h3>
					</div>
					<div class="my-model-wrapper col-group">
						<div class="content-box form-btns text--left mb-10">
							<?= $content; ?>
						</div>

						<?php if(isset($btn_label)) { ?>
						<div class="text--center cf">
							<button content="" class="btn mb-10 <?= isset($btn_class) ? $btn_class : ''; ?>"><?= $btn_label; ?></button>
						</div>
						<?php }?>

						<?php if(isset($link_label)) { ?>
						<div class="text--center cf">
							<a href="#" class="btn mb-10 <?= isset($link_class) ? $link_class : ''; ?>">
								<?php if(isset($link_icon) && $link_icon != ""){ ?>
									<i class="<?= $link_icon;?>"></i> <span class=""><?= $link_label; ?></span>
								<?php }else{?>
									<?= $link_label; ?>
								<?php }?>
							</a>
						</div>
						<?php }?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>