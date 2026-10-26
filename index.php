<?php include 'header.php'; ?>

<body>

<?php
	// Start YOURLS engine
	require_once( dirname(__FILE__).'/includes/load-yourls.php' );

	// Check if user is authenticated
	$is_authenticated = yourls_is_valid_user();

	// Make variables visible to function & UI
	$shorturl = $message = $title = $status = '';

	// Part to be executed if FORM has been submitted
	if ( isset( $_REQUEST['url'] ) && $_REQUEST['url'] != 'http://' && (!$requireAuth || $is_authenticated === true) ) {
		shorten();
	}

	function shorten() {
		$url     = $_REQUEST['url'];
		$keyword = isset( $_REQUEST['keyword'] ) ? $_REQUEST['keyword'] : '' ;
		$title   = isset( $_REQUEST['title'] ) ?  $_REQUEST['title'] : '' ;

		$return  = yourls_add_new_link( $url, $keyword, $title );
		
		global $shorturl, $message, $status, $title;

		$shorturl = isset( $return['shorturl'] ) ? $return['shorturl'] : '';
		$message  = isset( $return['message'] ) ? $return['message'] : '';
		$title    = isset( $return['title'] ) ? $return['title'] : '';
		$status   = isset( $return['status'] ) ? $return['status'] : '';
	}
?>

	<div class="container-fluid h-100">
		<div class="row justify-content-center align-items-center h-100">
			<div class="col-12 col-lg-10 col-xl-8 col-xxl-6 mt-5">
				<div class="card border-0 mt-5 glass-card">
					<?php if( (!$requireAuth || $is_authenticated === true) && isset($status) && $status == 'success' ):  ?>
						<?php $url = preg_replace("(^https?://)", "", $shorturl );  ?>

						<div class="close-container text-end mt-3 me-3">
							<button type="button" class="btn-close" id="close-shortened-screen" aria-label="关闭"></button>
						</div>

						<div class="card-body px-5 pb-5">
							<h2 class="text-center">短链接已生成</h2>
							
							<div class="row justify-content-center">
								<div class="col-10">
									<div class="input-group input-group-block mt-4 mb-3">
										<input type="text" class="form-control" value="<?php echo $shorturl; ?>" required>
										<button class="btn btn-primary py-2 px-5 mt-2 mt-md-0" type="submit" id="copy-button" data-shorturl="<?php echo $shorturl; ?>">复制</button>
									</div>
									<span class="info">查看统计详情：<a href="<?php echo $shorturl; ?>+"><?php echo $url; ?>+</a></span>
								</div>
							</div>
						</div>
					<?php elseif( $requireAuth && $is_authenticated !== true ): ?>
						<!-- 登录表单 -->
						<div class="text-center">
								<img src="<?php echo kwke_full_url($logo) ?>" alt="Logo" width="95px" class="mt-n5">
						</div>
						<div class="card-body px-md-5">
							<h2 class="text-center mb-4">需要登录</h2>
							<p class="text-center mb-4">请登录后使用短链接服务。</p>
							
							<?php if( isset($_REQUEST['username']) || isset($_REQUEST['password']) ): ?>
								<div class="alert alert-danger alert-dismissible fade show" role="alert">
									<span><?php echo $is_authenticated; ?></span>
									<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
								</div>
							<?php endif; ?>

							<form method="post" action="<?php echo YOURLS_SITE; ?>/admin/index.php?redirect=/index.php">
								<div class="mb-3">
									<label for="username" class="form-label">用户名</label>
									<input type="text" id="username" name="username" class="form-control" autocomplete="username" required>
								</div>
								<div class="mb-3">
									<label for="password" class="form-label">密码</label>
									<input type="password" id="password" name="password" class="form-control" autocomplete="current-password" required>
								</div>
								<div class="d-grid">
									<?php echo yourls_nonce_field('admin_login', 'nonce', false, false); ?>
									<button type="submit" class="btn btn-primary">登录</button>
								</div>
							</form>
							
							<div class="text-center mt-3">
								<a href="<?php echo YOURLS_SITE; ?>/admin/" class="text-decoration-none">前往管理后台</a>
							</div>
						</div>
					<?php else: ?>
						<!-- 短链创建表单 -->
						<div class="text-center">
								<img src="<?php echo kwke_full_url($logo) ?>" alt="Logo" width="95px" class="mt-n5">
						</div>
						<div class="card-body px-md-5">
							<p><?php $description ?></p>

							<?php if ( isset( $_REQUEST['url'] ) && $_REQUEST['url'] != 'http://' ): ?>
								<?php if (strpos($message,'added') === false): ?>
									<div class="alert alert-danger alert-dismissible fade show" role="alert">
										<span>出错了：<?php echo $message; ?></span>
										<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
									</div>	    
								<?php endif; ?>
							<?php endif; ?>

							<?php if( defined('YOURLS_USER') ): ?>
								<div class="alert alert-success alert-dismissible fade show" role="alert">
									<span>欢迎，<?php echo YOURLS_USER; ?>！</span>
									<a href="<?php echo YOURLS_SITE; ?>/admin/index.php?action=logout&nonce=<?php echo yourls_create_nonce('admin_logout', 'logout'); ?>" class="alert-link ms-2">退出登录</a>
								</div>
							<?php endif; ?>

							<form id="shortenlink" method="post" action="">
								<div class="input-group input-group-block mt-4 mb-3">
									<input type="url" name="url" id="url" class="form-control" placeholder="粘贴长链接，缩短并分享" aria-label="粘贴长链接" aria-describedby="shorten-button" required>
									<input class="btn btn-primary py-2 px-4 mt-2 mt-md-0" type="submit" id="shorten-button" value="缩短链接" />
								</div>
								<?php if ($enableCustomURL): ?>
									<a class="btn btn-sm btn-white text-black-50" data-bs-toggle="collapse" href="#customise-link" role="button" aria-expanded="false" aria-controls="customise-link">
										<img src="<?php echo YOURLS_SITE ?>/assets/svg/custom-url.svg" alt="选项"> 自定义链接
									</a>
									<div class="collapse" id="customise-link">
										<div class="mt-2 card card-body">
											<div class="d-flex  align-items-center">
												<span class="me-2"><?php echo preg_replace("(^https?://)", "", YOURLS_SITE ); ?>/</span>
												<input type="text" name="keyword" class="form-control form-control-sm" placeholder="自定义后缀" aria-label="自定义后缀">
											</div>
										</div>
									</div>
								<?php endif; ?>
							</form>
						</div>
					<?php endif; ?>
				</div>
				<div class="d-flex flex-column flex-md-row align-items-center justify-content-center my-3">
					<span class="text-white fw-light" style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);">&copy; <?php echo date("Y"); ?> <?php echo $shortTitle ?></span>
					<div class="ms-3">
						<?php foreach ($footerLinks as $link): ?>
							<a class="bold-link me-3 text-white text-decoration-none" href="<?php echo kwke_full_url($link['url']) ?>" target="_blank" rel="noopener" style="text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.7);"><span><?php echo htmlspecialchars($link['name']) ?></span></a>
						<?php endforeach ?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php include 'footer.php'; ?>
</body>
</html>
