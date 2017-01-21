<?php

use Engelsystem\Database\DB;

/**
 * @return string
 */
function admin_free_title()
{
    return _('Free angels');
}

/**
 * @return string
 */
function admin_free()
{
    global $privileges;

    $search = '';
    if (isset($_REQUEST['search'])) {
        $search = strip_request_item('search');
    }

    $angelTypeSearch = '';
    if (empty($_REQUEST['angeltype'])) {
        $_REQUEST['angeltype'] = '';
    } else {
        $angelTypeSearch = ' INNER JOIN `UserAngelTypes` ON (`UserAngelTypes`.`angeltype_id` = '
            . DB::getPdo()->quote($_REQUEST['angeltype'])
            . ' AND `UserAngelTypes`.`user_id` = `User`.`UID`';
        if (isset($_REQUEST['confirmed_only'])) {
            $angelTypeSearch .= ' AND `UserAngelTypes`.`confirm_user_id`';
        }
        $angelTypeSearch .= ') ';
    }

    $angel_types_source = DB::select('SELECT `id`, `name` FROM `AngelTypes` ORDER BY `name`');
    $angel_types = [
        '' => 'alle Typen'
    ];
    foreach ($angel_types_source as $angel_type) {
        $angel_types[$angel_type['id']] = $angel_type['name'];
    }

    $users = DB::select('
          SELECT `User`.* 
          FROM `User` 
          ' . $angelTypeSearch . '
          LEFT JOIN `ShiftEntry` ON `User`.`UID` = `ShiftEntry`.`UID` 
          LEFT JOIN `Shifts`
              ON (
                  `ShiftEntry`.`SID` = `Shifts`.`SID`
                  AND `Shifts`.`start` < ?
                  AND `Shifts`.`end` > ?
              )
          WHERE `User`.`Gekommen` = 1
          AND `Shifts`.`SID` IS NULL 
          GROUP BY `User`.`UID` 
          ORDER BY `Nick`
        ',
        [
            time(),
            time(),
        ]
    );

    $free_users_table = [];
    if ($search == '') {
        $tokens = [];
    } else {
        $tokens = explode(' ', $search);
    }
    foreach ($users as $usr) {
        if (count($tokens) > 0) {
            $match = false;
            $index = join('', $usr);
            foreach ($tokens as $t) {
                if (stristr($index, trim($t))) {
                    $match = true;
                    break;
                }
            }
            if (!$match) {
                continue;
            }
        }

        $free_users_table[] = [
            'name'        => User_Nick_render($usr),
            'shift_state' => User_shift_state_render($usr),
            'dect'        => $usr['DECT'],
            'jabber'      => $usr['jabber'],
            'email'       => $usr['email_by_human_allowed'] ? $usr['email'] : glyph('eye-close'),
            'actions'     =>
                in_array('admin_user', $privileges)
                    ? button(page_link_to('admin_user') . '&amp;id=' . $usr['UID'], _('edit'), 'btn-xs')
                    : ''
        ];
    }
    return page_with_title(admin_free_title(), [
        form([
            div('row', [
                div('col-md-4', [
                    form_text('search', _('Search'), $search)
                ]),
                div('col-md-4', [
                    form_select('angeltype', _('Angeltype'), $angel_types, $_REQUEST['angeltype'])
                ]),
                div('col-md-2', [
                    form_checkbox('confirmed_only', _('Only confirmed'), isset($_REQUEST['confirmed_only']))
                ]),
                div('col-md-2', [
                    form_submit('submit', _('Search'))
                ])
            ])
        ]),
        table([
            'name'        => _('Nick'),
            'shift_state' => '',
            'dect'        => _('DECT'),
            'jabber'      => _('Jabber'),
            'email'       => _('E-Mail'),
            'actions'     => ''
        ], $free_users_table)
    ]);
}
