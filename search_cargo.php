<?php
$forms = new ClassForms(get_bloginfo("language"));

function getTable($forms)
{
    function getCargoComstilUrl($type)
    {
        $cargoComstilUrl = [
            '1' => 'mezhdunarodnyie-avtoperevozki/gruzyi-vse-zayavki/order-',
            '15' => 'morskie-kontejnernyie-perevozki/gruzyi-na-sajte-poisk/order-',
            '44' => 'zheleznodorozhnyie-gruzoperevozki/zhd-gruzyi-vse-zayavki/order-',
            '43' => 'avia-gruzoperevozki/avia-gruzyi-vse-zayavki/order-',
            '5' => 'dostavka-posyilok/zakazyi-na-dostavku-posyilok/order-',
            '0' => 'passazhirskie-perevozki/zakazyi-ot-passazhirov/order-'
        ];

        $autoStr = '1000,3,19,2,4,9,1001,10,5,12,20,21,1002,7,23,22,11,8,1003,27,28,26,13,29,45,32,1005,37,35,14,1006,16';

        $auto = explode(',',$autoStr);

        if(in_array($type,$auto)) return $cargoComstilUrl['1'];

        return  $cargoComstilUrl[$type];
    }

    $host     = REMOTE_DB_HOST; // адрес сервера
    $database = REMOTE_DB_NAME; // имя базы данных
    $user     = REMOTE_DB_USER; // имя пользователя
    $password = REMOTE_DB_PASSWORD; // пароль

    $link = mysqli_connect($host, $user, $password, $database, '3306')
    or die("Ошибка " . mysqli_connect_error());

    mysqli_query($link, "SET NAMES '" . REMOTE_DB_CHARSET . "'");

    $sufix = '';

    if ($_GET['type'] && $_GET['type'] == 5) {
        $sufix = "_post";
    }

    if ($_GET['type'] === '0') {
        $sufix = "_passengers";
    }

    $countries = [];
    $language  = $forms->getLang();
    $query     = "SELECT country_group, id_country, country_name_$language as country_name, alpha3, country_name_ru_from, country_name_ru_to FROM country ORDER BY country_group ASC, country_name_$language ASC";
    $table     = mysqli_query($link, $query);
    if (mysqli_num_rows($table) > 0) {
        while ($data = mysqli_fetch_array($table)) {
            $countries[$data['id_country']]['name']   = $data['country_name'];
            $countries[$data['id_country']]['alpha3'] = $data['alpha3'];
            $countries[$data['id_country']]['name_from']   = $data['country_name_ru_from'];
            $countries[$data['id_country']]['name_to']   = $data['country_name_ru_to'];
        }
    }

    $city = [];
    $query     = "SELECT id_city, city_name_$language as city_name FROM city";
    $table     = mysqli_query($link, $query);
    if (mysqli_num_rows($table) > 0) {
        while ($data = mysqli_fetch_array($table)) {
            $city[$data['id_city']]['name'] = $data['city_name'];
        }
    }

    $cargoVolume = [];
    $query       = "SELECT id, cargo_volume_$language as cargo_volume FROM cargo_volume";
    $table       = mysqli_query($link, $query);
    if (mysqli_num_rows($table) > 0) {
        while ($data = mysqli_fetch_array($table)) {
            $cargoVolume[$data['id']] = $data['cargo_volume'];
        }
    }

    $cargoTypes = [];
    $query      = "SELECT id, cargo_type_$language as cargo_type FROM cargo_type" . $sufix;
    $table      = mysqli_query($link, $query);
    if (mysqli_num_rows($table) > 0) {
        while ($data = mysqli_fetch_array($table)) {
            $cargoTypes[$data['id']] = $data['cargo_type'];
        }
    }

    $typesTransport = [];
    $query          = "SELECT id, transport_type_$language as title FROM transport_type" . $sufix;
    $table          = mysqli_query($link, $query);
    if (mysqli_num_rows($table) > 0) {
        while ($data = mysqli_fetch_array($table)) {
            $typesTransport[$data['id']] = $data['title'];
        }
    }


    $perPage      = 50;
    $ends_count   = 1;
    $middle_count = 2;
    $dots         = false;
    $page         = (isset($_GET['ls_page'])) ? (int)$_GET['ls_page'] : 1;
    $startAt      = $perPage * ($page - 1);

    $queryWhere = '';

    if ($_GET['export']) {
        $queryWhere .= " AND `export` = " . $_GET['export'] . " ";
    }
    if ($_GET['import']) {
        $queryWhere .= " AND `import` = " . $_GET['import'] . " ";
    }
    if ($_GET['container_type']) {
        $queryWhere .= " AND `container_type` = " . $_GET['container_type'] . " ";
    }
    if ($_GET['type'] && $_GET['type'] != 1 && $_GET['type'] != 5) {
        $queryWhere .= " AND `type` = " . $_GET['type'] . " ";
    }
    if($_GET['type2'] && ! empty($_GET['type2'])) {
        $queryWhere .= " AND `type` = " . $_GET['type2'] . " ";
    }
    if($_GET['type3'] && ! empty($_GET['type3'])) {
        $queryWhere .= " AND `type` = " . $_GET['type3'] . " ";
    }
    if($_GET['type4'] && ! empty($_GET['type4'])) {
        $queryWhere .= " AND `type` = " . $_GET['type4'] . " ";
    }

    if (!empty($queryWhere)) {
        $query = "SELECT COUNT(*) as total FROM movers_cargo".$sufix." WHERE `hidden` = '0'" . $queryWhere . " ORDER BY `id` DESC";
    } else {
        $query = "SELECT COUNT(*) as total FROM movers_cargo".$sufix." WHERE `hidden` = '0'";
    }

    $r = mysqli_fetch_assoc(mysqli_query($link, $query));

    $totalPages = ceil($r['total'] / $perPage);

    $links = "";

    function addToURL( $key, $value, $url) {
        $info = parse_url( $url );
        parse_str( $info['query'], $query );
        return $info['scheme'] . '://' . $info['host'] . $info['path'] . '?' . http_build_query( $query ? array_merge( $query, array($key => $value ) ) : array( $key => $value ) );
    }

    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $page) {
            $links .= '<li class="active"><a href="">' . $page . '</a></li>';
            $dots  = true;
        } else {
            if ($i <= $ends_count || ($page && $i >= $page - $middle_count && $i <= $page + $middle_count) || $i > $totalPages - $ends_count) {
                $url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                $url = addToURL('ls_page',$i,$url);

                $links .= '<li><a href="'.$url . '">' . $i . '</a></li>';
                $dots  = true;
            } elseif ($dots) {
                $links .= '<li><span>...</span></li>';
                $dots  = false;
            }
        }
    }

    if (!empty($queryWhere)) {
        $query = "SELECT * FROM `movers_cargo".$sufix."` WHERE `hidden` = '0'" . $queryWhere . " ORDER BY id DESC LIMIT $startAt, $perPage";
    } else {
        $query = "SELECT * FROM `movers_cargo".$sufix."` WHERE `hidden` = '0' ORDER BY id DESC LIMIT $startAt, $perPage";
    }

    $res = mysqli_query($link, $query) or die(mysqli_error($link));

    $totalFromPage = mysqli_num_rows($res);

    if ($totalFromPage > 0) {

        $entry = "";

        $i = 0;

        while ($row = mysqli_fetch_array($res)) {
            $i++;
            $class = "cargo-table_row";

            if ($totalFromPage-1 == $i) {
                $class .= " fixed_block_stop";
            }

            if($sufix !== '_passengers'){
                $entry .= '<div class="' . $class . '" data-id="'.$row['id'].'">
                            <div class="container" data-row="'.json_encode($row).'">
                                <div class="cargo-table_column">
                                    <strong>Откуда</strong>
                                    <span><img src="/wp-content/themes/twentytwenty/assets/img/blank.gif" class="flag flag-' . strtolower($countries[$row["export"]]['alpha3']) . '"><b style="padding-right:5px">' . strtoupper($countries[$row["export"]]['alpha3']) . '</b>' . $countries[$row["export"]]['name'] . '</span>
                                    <span class="span-city">'.$city[$row['export_city']]['name'].'</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Куда</strong>
                                    <span><img src="/wp-content/themes/twentytwenty/assets/img/blank.gif" class="flag flag-' . strtolower($countries[$row["import"]]['alpha3']) . '"><b style="padding-right:5px">' . strtoupper($countries[$row["import"]]['alpha3']) . '</b>' . $countries[$row["import"]]['name'] . '</span>
                                    <span class="span-city">'.$city[$row['import_city']]['name'].'</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Дата погрузки</strong>
                                    <span>' . $row["date"] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Тип транспорта</strong>
                                    <span>' . $typesTransport[$row["type"]] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Тип груза</strong>
                                    <span>' . $cargoTypes[$row["name"]] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Объем/Вес</strong>
                                    <span data-volume="' . $row["volume"] . '">' . $cargoVolume[$row["volume"]] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Заказчик</strong>';

                                    if($row["comstil_id"] != 0){
                                        $entry .= '<a href="https://com-stil.com/' . getCargoComstilUrl($row['type']) . $row["comstil_id"] . '" target="_blank">Открыть заявку</a>';
                                    } else {
                                        $entry .= '<a href="javascript:void(0)" onclick="window.open(\''.get_site_url().'/order-cargo-info/?id='.$row['id'].'&t='.$_GET['type'].'&modal-lang=ru\',\'socialPopupWindow\',\'location=no,width=600,height=600,scrollbars=yes,top=100,left=700,resizable = no\')">Открыть заявку</a>';
                                    }

                                $entry .= '</div>
                            </div>
                        </div>';
            } else {
                $entry .= '<div class="' . $class . '" data-id="'.$row['id'].'">
                            <div class="container">
                                <div class="cargo-table_column">
                                    <strong>Откуда</strong>
                                    <span><img src="/wp-content/themes/twentytwenty/assets/img/blank.gif" class="flag flag-' . strtolower($countries[$row["export"]]['alpha3']) . '"><b style="padding-right:5px">' . strtoupper($countries[$row["export"]]['alpha3']) . '</b>' . $countries[$row["export"]]['name'] . '</span>
                                    <span class="span-city">'.$city[$row['export_city']]['name'].'</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Куда</strong>
                                    <span><img src="/wp-content/themes/twentytwenty/assets/img/blank.gif" class="flag flag-' . strtolower($countries[$row["import"]]['alpha3']) . '"><b style="padding-right:5px">' . strtoupper($countries[$row["import"]]['alpha3']) . '</b>' . $countries[$row["import"]]['name'] . '</span>
                                    <span class="span-city">'.$city[$row['import_city']]['name'].'</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Дата отправки</strong>
                                    <span>' . $row["date"] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Тип транспорта</strong>
                                    <span>' . $typesTransport[$row["type"]] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Кол-во пассажиров</strong>
                                    <span>' . $row["passenger_number"] . '</span>
                                </div>
                                <div class="cargo-table_column">
                                    <strong>Заказчик</strong>';

                                    if($row["comstil_id"] != 0){
                                        $entry .= '<a href="https://com-stil.com/' . getCargoComstilUrl('0') . $row["comstil_id"] . '" target="_blank">Открыть заявку</a>';
                                    } else {
                                        $entry .= '<a href="javascript:void(0)" onclick="window.open(\''.get_site_url().'/order-cargo-info/?id='.$row['id'].'&t='.$_GET['type'].'&modal-lang=ru\',\'socialPopupWindow\',\'location=no,width=600,height=600,scrollbars=yes,top=100,left=700,resizable = no\')">Открыть заявку</a>';
                                    }

                                $entry .= '</div>
                            </div>
                        </div>';
            }
        }
    }

    return [
        'entry' => $entry,
        'links' => $links,
        'count' => $r['total'],
        'countries' => $countries
    ];
}

$formData = getTable($forms);
?>

    <div class="cargo-filters">
        <div class="container">
            <div class="bread-crumbs">
                <ul>
                    <li><a href="/">Главная</a></li>
                    <li><?= $post->post_title ?></li>
                </ul>
            </div>
            <form method="get" id="form_table">
                <div class="cargo-filters_search">
                    <div class="cargo-filters_title">Вы в разделе поиска грузов:</div>
                    <div class="panel-cost_list wow fadeInDown">
                        <div class="panel-cost_item">
                            <div class="panel-cost_label">Укажите страну загрузки</div>
                            <select class="" id="export_country_select" name="export">
                                <option value="">Все страны</option>
                                <?= $forms->getApiData('/wp-admin/admin-ajax.php?action=get-country-list') ?>
                            </select>
                        </div>
                        <div class="panel-cost_item">
                            <div class="panel-cost_label">Укажите страну разгрузки</div>
                            <select class="" name="import" id="import_country_select">
                                <option value="">Все страны</option>
                                <?= $forms->getApiData('/wp-admin/admin-ajax.php?action=get-country-list') ?>
                            </select>
                        </div>
                        <div class="panel-cost_item">
                            <div class="panel-cost_label">Укажите тип груза</div>
                            <select onchange="showSelect(this)" name="type">
                                <option value="">Все типы грузов</option>
                                <option value="1" <?php if($_GET['type'] == 1): ?> selected <?php endif; ?> >Грузы для автоперевозок</option>
                                <option value="15" <?php if($_GET['type'] == 15): ?> selected <?php endif; ?> >Грузы для морских перевозок</option>
                                <option value="44" <?php if($_GET['type'] == 44): ?> selected <?php endif; ?> >Грузы для ж/д перевозок</option>
                                <option value="43" <?php if($_GET['type'] == 43): ?> selected <?php endif; ?> >Грузы для авиа-перевозок</option>
                                <option value="5" <?php if($_GET['type'] == 5): ?> selected <?php endif; ?> >Посылки и мелкие грузы</option>
                                <option value="0" <?php if($_GET['type'] === '0'): ?> selected <?php endif; ?> >Заказы пассажиров</option>
                            </select>
                        </div>

                        <div class="panel-cost_item" id="select_body_type" style="<?php if($_GET['type'] == 1): ?> display: block; <?php else:?> display: none; <?php endif; ?>">
                            <div class="panel-cost_label">Укажите тип кузова</div>
                            <select class="" name="type2">
                                <!--                        --><? //= $forms->getApiData('/wp-admin/admin-ajax.php?action=get-transport-type-list') ?>
                                <option value="">Все типы кузова</option>
                                <option value="3" <?php if($_GET['type2'] == 3): ?> selected <?php endif; ?> >Рефрижератор</option>
                                <option value="19" <?php if($_GET['type2'] == 19): ?> selected <?php endif; ?> >Рефрижератор автопоезд</option>
                                <option value="4" <?php if($_GET['type2'] == 4): ?> selected <?php endif; ?> >Тентованный автопоезд с прицепом</option>
                                <option value="2" <?php if($_GET['type2'] == 2): ?> selected <?php endif; ?> >Тентованный полуприцеп</option>
                                <option value="9" <?php if($_GET['type2'] == 9): ?> selected <?php endif; ?> >Автоперевозка контейнера</option>
                                <option value="10" <?php if($_GET['type2'] == 10): ?> selected <?php endif; ?> >Изотерм или Цельномет.</option>
                                <option value="5" <?php if($_GET['type2'] == 5): ?> selected <?php endif; ?> >Мегатрейлер полуприцеп тенованный</option>
                                <option value="12" <?php if($_GET['type2'] == 12): ?> selected <?php endif; ?> >Перевозки Сборного груза</option>
                                <option value="20" <?php if($_GET['type2'] == 20): ?> selected <?php endif; ?> >Юмбо тент</option>
                                <option value="21" <?php if($_GET['type2'] == 21): ?> selected <?php endif; ?> >Юмбо цельномет</option>
                                <option value="7" <?php if($_GET['type2'] == 7): ?> selected <?php endif; ?> >Автовоз</option>
                                <option value="23" <?php if($_GET['type2'] == 23): ?> selected <?php endif; ?> >Вешеловоз перевозка одежды</option>
                                <option value="22" <?php if($_GET['type2'] == 22): ?> selected <?php endif; ?> >Негабарит</option>
                                <option value="11" <?php if($_GET['type2'] == 11): ?> selected <?php endif; ?> >Перевозки Опасного груза ADR</option>
                                <option value="8" <?php if($_GET['type2'] == 8): ?> selected <?php endif; ?> >Трейлер трал-платформа</option>
                                <option value="27" <?php if($_GET['type2'] == 27): ?> selected <?php endif; ?> >Бус грузовой</option>
                                <option value="28" <?php if($_GET['type2'] == 28): ?> selected <?php endif; ?> >Бус рефрижератор</option>
                                <option value="26" <?php if($_GET['type2'] == 26): ?> selected <?php endif; ?> >Бус фургон</option>
                                <option value="13" <?php if($_GET['type2'] == 13): ?> selected <?php endif; ?> >Самосвал</option>
                                <option value="29" <?php if($_GET['type2'] == 29): ?> selected <?php endif; ?> >Тягач</option>
                                <option value="45" <?php if($_GET['type2'] == 45): ?> selected <?php endif; ?> >Эвакуатор до 30т.</option>
                                <option value="32" <?php if($_GET['type2'] == 32): ?> selected <?php endif; ?> >Эвакуатор до 3т.</option>
                                <option value="37" <?php if($_GET['type2'] == 37): ?> selected <?php endif; ?> >Зерновоз</option>
                                <option value="35" <?php if($_GET['type2'] == 35): ?> selected <?php endif; ?> >Лесовоз</option>
                                <option value="14" <?php if($_GET['type2'] == 14): ?> selected <?php endif; ?> >Цистерна, бочка, термос</option>
                                <option value="16" <?php if($_GET['type2'] == 16): ?> selected <?php endif; ?> >Другой транспорт</option>
                            </select>
                        </div>

                        <div class="panel-cost_item" id="select_cargo_type" style="<?php if($_GET['type'] == 5): ?> display: block; <?php else:?> display: none; <?php endif; ?>">
                            <div class="panel-cost_label">Укажите тип груза</div>
                            <select class="" name="type3">
                                <!--                        --><? //= $forms->getApiData('/wp-admin/admin-ajax.php?action=get-post-transport-type-list') ?>
                                <option value="">Все типы кузова</option>
                                <option value="1" <?php if($_GET['type3'] == 1): ?> selected <?php endif; ?> >Бус почтовик</option>
                                <option value="2" <?php if($_GET['type3'] == 2): ?> selected <?php endif; ?> >Грузовое такси</option>
                                <option value="4" <?php if($_GET['type3'] == 4): ?> selected <?php endif; ?> >Микроавтобус</option>
                                <option value="3" <?php if($_GET['type3'] == 3): ?> selected <?php endif; ?> >Минивэн</option>
                                <option value="6" <?php if($_GET['type3'] == 6): ?> selected <?php endif; ?> >Экспресс-доставка</option>
                                <option value="5" <?php if($_GET['type3'] == 5): ?> selected <?php endif; ?> >Другой почтовый транспорт</option>
                            </select>
                        </div>

                        <div class="panel-cost_item" id="select_passengers_type" style="<?php if($_GET['type'] === '0' || $filterType === '0'): ?> display: block; <?php else:?> display: none; <?php endif; ?>">
                            <div class="panel-cost_label">Тип транспорта</div>
                            <select class="" name="type4">
                                <option value="">Выберите тип транспорта</option>
                                <option value="">Все виды автоперевозок</option>
                                <optgroup label="">
                                    <option value="5" <?php if($_GET['type4'] == 5): ?> selected <?php endif; ?>>Автобус двухэтажный</option>
                                    <option value="2" <?php if($_GET['type4'] == 2): ?> selected <?php endif; ?>>Бус грузопассажирский</option>
                                    <option value="3" <?php if($_GET['type4'] == 3): ?> selected <?php endif; ?>>Бус минивэн</option>
                                    <option value="6" <?php if($_GET['type4'] == 6): ?> selected <?php endif; ?>>Микроавтобус</option>
                                    <option value="1" <?php if($_GET['type4'] == 1): ?> selected <?php endif; ?>>Такси</option>
                                    <option value="7" <?php if($_GET['type4'] == 7): ?> selected <?php endif; ?>>Другой пассажирский транспорт</option>
                                </optgroup>
                            </select>
                        </div>

                        <div class="panel-cost_item">
                            <button class="panel-cost_submit main-btn" type="submit"
                                    onclick="document.getElementById('form_table').submit()">Поиск
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showSelect(a) {
            let val = (a.value || a.options[a.selectedIndex].value);  //crossbrowser solution =)

            document.getElementById('select_body_type').style.display = 'none';
            document.getElementById('select_cargo_type').style.display = 'none';

            if (val == 1) {
                document.getElementById('select_body_type').style.display = 'block';
                document.getElementById('select_cargo_type').style.display = 'none';
            } else if (val == 5) {
                document.getElementById('select_body_type').style.display = 'none';
                document.getElementById('select_cargo_type').style.display = 'block';
            }
        }

        function getQueryParams(qs) {
            qs = qs.split('+').join(' ');

            var params = {},
                tokens,
                re = /[?&]?([^=]+)=([^&]*)/g;

            while (tokens = re.exec(qs)) {
                params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
            }

            return params;
        }

        let queryParams = getQueryParams(document.location.search);

        if(queryParams.export) {
            document.getElementById('export_country_select').value = queryParams.export;
        }

        if(queryParams.import) {
            document.getElementById('import_country_select').value = queryParams.import;
        }
    </script>

<div class="container">
    <?php
        $fExport = $_GET['export'] ?? null;
        $fImport = $_GET['import'] ?? null;
        $fType = $_GET['type'] ?? null;

        $types = [
                '1' => 'Грузы для автоперевозок',
                '15' => 'Грузы для морских перевозок',
                '44' => 'Грузы для ж/д перевозок',
                '43' => 'Грузы для авиа-перевозок',
                '5' => 'Посылки и мелкие грузы',
                '0' => 'Заказы пассажиров'
        ];
    ?>

    <?php if($fExport && !$fImport):?>
        <span>Заявки по грузам. <?= $types[$fType] ?? 'Все типы грузов' ?> в направлении из <?=  $formData['countries'][$fExport]['name_from'] ?> найдено предложений <?= $formData['count'] ?></span>
    <?php elseif($fImport && !$fExport):?>
        <span>Заявки по грузам. <?= $types[$fType] ?? 'Все типы грузов' ?> в направлении в <?=  $formData['countries'][$fImport]['name_to'] ?> найдено предложений <?= $formData['count'] ?></span>
    <?php elseif($fImport && $fExport):?>
        <span>Заявки по грузам. <?= $types[$fType] ?? 'Все типы грузов' ?> в направлении из <?=  $formData['countries'][$fExport]['name_from'] ?> в <?=  $formData['countries'][$fImport]['name_to'] ?> найдено предложений <?= $formData['count'] ?></span>
    <?php else: ?>
        <span>Заявки по грузам. <?= $types[$fType] ?? 'Все типы грузов' ?> найдено предложений <?= $formData['count'] ?></span>
    <?php endif;?>
</div>

<?php if($_GET['type'] != '0'): ?>
    <div class="cargo-table fixed_block_position" style="width: 1903px; height: 86px;">
        <div class="cargo-table_row head fixed_block absolute">
            <div class="container">
                <div class="cargo-table_column">Место погрузки</div>
                <div class="cargo-table_column">Место разгрузки</div>
                <div class="cargo-table_column">Дата
                    погрузки
                </div>
                <div class="cargo-table_column">Тип транспорта</div>
                <div class="cargo-table_column">Тип груза</div>
                <div class="cargo-table_column">Объем/Вес</div>
                <div class="cargo-table_column">Заказчик</div>
            </div>
        </div>
        <?= $formData['entry']; ?>
    </div>
<?php else: ?>
    <div class="cargo-table fixed_block_position" style="width: 1903px; height: 86px;">
        <div class="cargo-table_row head fixed_block absolute">
            <div class="container">
                <div class="cargo-table_column">Откуда</div>
                <div class="cargo-table_column">Куда</div>
                <div class="cargo-table_column">Дата отправки
                </div>
                <div class="cargo-table_column">Тип транспорта</div>
                <div class="cargo-table_column">Кол-во пассажиров</div>
                <div class="cargo-table_column">Заказчик</div>
            </div>
        </div>
        <?= $formData['entry']; ?>
    </div>
<?php endif?>

    <div class="container">
        <div class="page-navigation">
            <ul>
                <?= $formData['links']; ?>
            </ul>
        </div>
    </div>


    <div class="text-page container">
        <?php the_content(__('Continue reading', 'twentytwenty')); ?>
    </div>

<?php include('_bottom_slider.php') ?>
