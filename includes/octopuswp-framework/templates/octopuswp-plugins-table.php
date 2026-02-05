<?php
$octopus_plugins = OctopusWP_Framework::get_octopus_plugins();
?>
<table id="octopuswp-plugins-table" class="table">
    <thead>
    <tr>
        <th class="status">狀態</th>
        <th class="expiration-time">到期日</th>
        <th class="name">名稱</th>
        <th class="version">版本</th>
        <th class="description">描述</th>
        <th class="activation-code">啟用碼</th>
        <th class="action"></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($octopus_plugins as $octopus_plugin):
        $option = get_option($octopus_plugin['id']);
        ?>
        <tr>
            <td class="status">
                <?php if(@$option['activated']): ?>
                    <div class="activated">已啟用</div>
                <?php else: ?>
                    <div class="inactivated">未啟用</div>
                <?php endif; ?>
            </td>
            <td class="expiration-time">
                <div><?=@$option['expiration_time']?></div>
            </td>
            <td class="name"><?=$octopus_plugin['name']?></td>
            <td class="name"><?=$octopus_plugin['version']?></td>
            <td class="description"><?=$octopus_plugin['description']?></td>
            <td class="activation-code">
                <input type="text" class="octopuswp-activation-code" value="<?=@$option['activation_code']?>">
                <input type="hidden" class="octopuswp-plugin-id" value="<?=@$octopus_plugin['id']?>">
            </td>
            <td class="action">
                <?php if(@$option['activated']): ?>
                    <button class="button button-secondary deactivate">停用</button>
                <?php else: ?>
                    <button class="button button-primary activate">啟用</button>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>