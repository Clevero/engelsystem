<?php

/**
 * Returns all needed angeltypes and already taken needs.
 *
 * @param Shift $shift          
 */
function NeededAngelTypes_by_shift($shift) {
  $needed_angeltypes_source = sql_select("
        SELECT `NeededAngelTypes`.*, `AngelTypes`.`name`, `AngelTypes`.`restricted`
        FROM `NeededAngelTypes`
        JOIN `AngelTypes` ON `AngelTypes`.`id` = `NeededAngelTypes`.`angel_type_id`
        WHERE (`shift_id`=" . sql_escape($shift['SID']) . "
        OR `room_id`=" . sql_escape($shift['RID']) . ")
        AND `count` > 0
        ORDER BY `room_id` DESC
        ");
  if ($needed_angeltypes_source === false)
    return false;
  
  $needed_angeltypes_merged = array();
  foreach ($needed_angeltypes_source as $angeltype)
    $needed_angeltypes_merged[$angeltype['id']] = $angeltype;
  
  $needed_angeltypes = array();
  foreach ($needed_angeltypes_merged as $angeltype) {
    $shift_entries = ShiftEntries_by_shift_and_angeltype($shift['SID'], $angeltype['id']);
    if ($shift_entries === false)
      return false;
    
    $angeltype['taken'] = count($shift_entries);
    $needed_angeltypes[] = $angeltype;
  }
  
  return $needed_angeltypes;
}

?>