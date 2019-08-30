<h1><?=$data["config"]["title"]?></h1>
        <form method="post">
            <?= wp_nonce_field($data['nonceIDs']['action'], $data['nonceIDs']['field']); ?>
            <div id="aeriaApp-<?=$data["config"]['id']?>" class="aeriaApp">
                <script>
                    window.aeriaMetaboxes = window.aeriaMetaboxes || [];
                    window.aeriaMetaboxes.push(<?=wp_json_encode($data["config"]); ?>);
                </script>
            </div>
            <?= submit_button(); ?>
        </form>