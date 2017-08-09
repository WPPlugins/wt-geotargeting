
<style type="text/css">

</style>

<div class="wrap">

	<div id="icon-options-general" class="icon32"></div>
	<h2><?php esc_attr_e( 'WT GeoLocation: Как настроить геотаргетинг?', 'wp_admin_style' ); ?></h2>

	<div id="poststuff">

		<div id="post-body" class="metabox-holder columns-2">

			<!-- main content -->
			<div id="post-body-content">

				<div class="meta-box-sortables ui-sortable">

					<div class="postbox">
						<div class="inside">
							<p>
								<b>WT GeoTargeting</b> — плагин для CMS WordPress, позволяющий с помощью Шорткодов настраивать геотаргетинг на страницах сайта. Местоположение посетителя определяется на основе базы IP-адресов "IpGeoBase".
							</p>
							<p>
								На официальном сайте есть возможность приобрести расширенную версию плагина. Расширенная версия позволяет управлять контактами в панели администратора, использовать упрощенную форму шорткодом и PHP-методы.
							</p>

							<h3><span style="color: red;"><?php esc_attr_e('Внимание!!!', 'wp_admin_style'); ?></span></h3>
							<p>
								Не смотря на успешность отображения контента ориентированного на местоположение пользователей, есть у геотаргетинга и <strong>обратная сторона - погрешность и, в редких случаях, невозможность определить местоположение посетителя</strong>. Это достаточно важный момент и его всегда стоит учитывать, иначе Вы можете потерять часть потенциальных клиентов. Мы рекомендуем реализовать структуру вашего сайта таким образом, что-бы пользователь в случае необходимости мог поменять своё месторасположение и найти необходимую контактную информацию.
							</p>

							<h3><span><?php esc_attr_e( 'Полезные ссылки:', 'wp_admin_style' ); ?></span></h3>
							<p>
								<a href="http://web-technology.biz/cms-wordpress/plugin-wt-geotargeting" target="_blank">Официальная страница плагина</a><br>
								<a href="http://web-technology.biz/cms-wordpress/plugin-wt-geotargeting-for-cms-wordpress" target="_blank">Документация</a><br>
								<a href="https://wordpress.org/plugins/wt-geotargeting" target="_blank">Страница плагина на WordPress.org</a><br>
								<a href="https://vk.com/topic-40886935_33381010" target="_blank">Тема Вконтакте для обсуждения плагина</a><br>
								<a href="http://ipgeobase.ru/" target="_blank">База IP-адресов "IpGeoBase"</a>
							</p>
						</div>



					</div>
					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables .ui-sortable -->

			</div>
			<!-- post-body-content -->

			<!-- sidebar -->
			<div id="postbox-container-1" class="postbox-container">

				<div class="meta-box-sortables">
					<div id="city_info" style="position: relative; top: -90px;"></div>

					<div class="postbox">

						<h3><span><?php esc_attr_e(
									'Справочник городов', 'wp_admin_style'
								); ?></span></h3>
						<div class="inside">
							<p>Выбрав город Вы можете посмотреть дополнительные параметры для составления условий геотаргетинга.</p>
							<form action="#city_info" method="get"> 
								<input type="hidden" name="page" value="<?php if (!empty($_GET['page'])) echo $_GET['page']; ?>"> <!-- Текущая страница -->
								<select name="ipgeobase_city_id">
									<?php
										foreach ($this->getCities() as $key => $value) { ?>
										<option value="<?php echo $key; ?>" 
											<?php 
											if (!empty($_GET['ipgeobase_city_id']) && $key == $_GET['ipgeobase_city_id']) 
												echo ' selected="selected" ';
											?>
											><?php echo $value; ?></option>
										<?php
										}
									?>
								</select>
								<input type="submit" value="Просмотр">
							</form>

							<?php
								if (!empty($_GET['ipgeobase_city_id'])){
									$city_info = $this->getCityInfo($_GET['ipgeobase_city_id']);
									?> <p> <?php	
									if (isset($city_info['city'])) echo '<b>Город:</b> '.$city_info['city'].'<br>';
									if (isset($city_info['region'])) echo '<b>Регион:</b> '.$city_info['region'].'<br>';
									if (isset($city_info['district'])) echo '<b>Округ:</b> '.$city_info['district'].'<br>';		
									?> </p> <?php
								}
							?>
						</div>
						
						<!-- .inside -->

					</div>
					<!-- .postbox -->

				</div>

				<div class="meta-box-sortables">

					<div class="postbox">

						<h3><span><?php esc_attr_e(
									'Тестирование работы плагина', 'wp_admin_style'
								); ?></span></h3>

						<div class="inside">
							
								<?php
									echo '<b>Ваш IP-адрес:</b> '.$this->geotargeting->ip. '<br>';
								    echo '<b>Страна:</b> '.$this->geotargeting->data['country']. '<br>';
								    echo '<b>Город:</b> '.$this->geotargeting->data['city']. '<br>';
								    echo '<b>Регион:</b> '.$this->geotargeting->data['region']. '<br>';
									echo '<b>Округ:</b> '.$this->geotargeting->data['district']. '<br>';
								    echo '<b>Широта (Latitude, lat):</b> '.$this->geotargeting->data['lat']. '<br>';
								    echo '<b>Долгота (Longitude, lng):</b> '.$this->geotargeting->data['lng']. '<br>';
								    echo '<b>Диапазон IP:</b> '.$this->geotargeting->data['inetnum']. '<br>';
								?>
							
						</div>
						<!-- .inside -->

					</div>
					<!-- .postbox -->

				</div>
				<!-- .meta-box-sortables -->

			</div>
			<!-- #postbox-container-1 .postbox-container -->

		</div>
		<!-- #post-body .metabox-holder .columns-2 -->

		<br class="clear">
	</div>
	<!-- #poststuff -->

</div> <!-- .wrap -->