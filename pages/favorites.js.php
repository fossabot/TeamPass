<?php

declare(strict_types=1);

/**
 * Teampass - a collaborative passwords manager.
 * ---
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * ---
 *
 * @project   Teampass
 * @file      favorites.js.php
 * ---
 *
 * @author    Nils Laumaillé (nils@teampass.net)
 *
 * @copyright 2009-2023 Teampass.net
 *
 * @license   https://spdx.org/licenses/GPL-3.0-only.html#licenseText GPL-3.0
 * ---
 *
 * @see       https://www.teampass.net
 */


use TeampassClasses\PerformChecks\PerformChecks;
use TeampassClasses\SessionManager\SessionManager;
use Symfony\Component\HttpFoundation\Request;
use TeampassClasses\Language\Language;
// Load functions
require_once __DIR__.'/../sources/main.functions.php';

// init
loadClasses();
$session = SessionManager::getSession();
$request = Request::createFromGlobals();
$lang = new Language(); 

if ($session->get('key') === null) {
    die('Hacking attempt...');
}

// Load config if $SETTINGS not defined
try {
    include_once __DIR__.'/../includes/config/tp.config.php';
} catch (Exception $e) {
    throw new Exception("Error file '/includes/config/tp.config.php' not exists", 1);
}

// Do checks
$checkUserAccess = new PerformChecks(
    dataSanitizer(
        [
            'type' => $request->request->get('type', '') !== '' ? htmlspecialchars($request->request->get('type')) : '',
        ],
        [
            'type' => 'trim|escape',
        ],
    ),
    [
        'user_id' => returnIfSet($session->get('user-id'), null),
        'user_key' => returnIfSet($session->get('key'), null),
    ]
);
// Handle the case
echo $checkUserAccess->caseHandler();
if ($checkUserAccess->checkSession() === false || $checkUserAccess->userAccessPage('favourites') === false) {
    // Not allowed page
    $session->set('system-error_code', ERR_NOT_ALLOWED);
    include $SETTINGS['cpassman_dir'] . '/error.php';
    exit;
}
?>


<script type='text/javascript'>
    // Open Item
    $('.fav-open').click(function() {
        if ($(this).data('item-id') !== '') {
            document.location.href = 'index.php?page=items&group=' + $(this).data('tree-id') + '&id=' + $(this).data('item-id');
        }
    });

    // Trash Item
    $('.fav-trash').click(function() {
        var item = $(this);
        if (item.data('item-id') !== '') {
            // Prepare modal
            showModalDialogBox(
                '#warningModal',
                '<i class="fas fa-minus-square fa-lg warning mr-2"></i><?php echo $lang->get('item_menu_del_from_fav'); ?>',
                '<?php echo $lang->get('confirm_del_from_fav'); ?>',
                '<?php echo $lang->get('confirm'); ?>',
                '<?php echo $lang->get('cancel'); ?>'
            );

            // Actions on modal buttons
            $(document).on('click', '#warningModalButtonClose', function() {
                // Nothing
            });
            $(document).on('click', '#warningModalButtonAction', function() {
                // SHow user
                toastr.remove();
                toastr.info('<?php echo $lang->get('in_progress'); ?> ... <i class="fas fa-circle-notch fa-spin fa-2x"></i>');

                // Launch ajax query                    
                $.post(
                    "sources/favourites.queries.php", {
                        type: "del_fav",
                        id: item.data('item-id')
                    },
                    function(data) {
                        item.closest('tr').remove();

                        // Manage case where no more favorites exist
                        if ($('tr').length === 1) {
                            $('#no-favorite').removeClass('hidden');
                            $('#favorites').addClass('hidden');
                        }

                        toastr.remove();
                        toastr.info(
                            '<?php echo $lang->get('done'); ?>',
                            '', {
                                timeOut: 1000
                            }
                        );
                    }
                );
            });
        }
    });
</script>
