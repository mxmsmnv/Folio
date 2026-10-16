<?php
$module = (string)file_get_contents(dirname(__DIR__) . '/Folio.module.php');
$process = (string)file_get_contents(dirname(__DIR__) . '/ProcessFolio.module.php');
$trait = (string)file_get_contents(dirname(__DIR__) . '/src/McpProviderTrait.php');
$checks = [
	'PocketWire opt-in is explicit' => str_contains($module, '"pocketWireProvider" => true'),
	'PocketWire SDK methods are exposed' => str_contains($trait, 'pocketWireProviderInfo()') && str_contains($trait, 'pocketWireManifest(User $actor)') && str_contains($trait, 'pocketWireRead(string $resource, string $identifier, array $query, User $actor)'),
	'PocketWire actor is current superuser' => str_contains($trait, 'pocketWireCanRead(User $actor)') && str_contains($trait, "wire('user')->id") && str_contains($trait, '$actor->isSuperuser()'),
	'private aggregates are superuser-only' => str_contains($trait, 'pocketWireCanRead($actor)') && str_contains($trait, "processWire' => ['superuser']") && str_contains($trait, '$actor->isSuperuser()') && str_contains($trait, 'WirePermissionException'),
	'only aggregate overview is advertised' => str_contains($trait, "'overview'") && str_contains($trait, 'mcpFolioStatus()') && str_contains($trait, "'actions' => []"),
	'private records and writes remain excluded' => str_contains($trait, "'private_records_exposed' => false") && str_contains($trait, "'write_tools' => false"),
	'module versions stay synchronized' => substr_count($module . $process, '"version" => 102') === 2 && substr_count($trait, "'moduleVersion' => '1.0.2'") === 2,
];
if(in_array(false, $checks, true)) { fwrite(STDERR, "Folio PocketWire provider contract failed.\n"); exit(1); }
echo "Folio PocketWire provider contract passed: " . count($checks) . " checks.\n";
