//delegated to tools that need it
    //$canister['install']('modulate');
    $optionalPylons = ['instance','local-instance'];
    if (
        key_exists('localDevEnabled', $canister) 
        && $canister['localDevEnabled']
    ) {
        $optionalPylons[] = 'local-dev';
    }
    if (is_readable("{$canister['installPath']}/local-dev.pylon")) { #TODO write a pylon installable
        require_once($envPylon);
    }
    if (key_exists('environmentName', $canister)) {
        $envRootPath = "env.{$canister['environmentName']}.root";
        foreach($canister['root']($envRootPath) as $key => $value){
            key_exists($key, $canister) || $canister[$key] = $value;
        }
        $optionalPylons[] = "env.{$canister['environmentName']}";
    }
    foreach($canister['root']("instance.root") as $key => $value){
        key_exists($key, $canister) || $canister[$key] = $value;
    }
    foreach($optionalPylons as $pylon) {
        $pylonPath = "{$canister['installPath']}/{$pylon}.pylon.php";
        if (is_readable($pylonPath)) {
			require_once($pylonPath);
		}
    }
    foreach($canister['root']('src/kickstart/host.root') as $key => $value){
        key_exists($key, $canister) || $canister[$key] = $value;
    }
    $canister['tether']('src/kickstart/pipe.tether', 'Application pipe unavailable.');
    //$canister['tether']('cache.tether', 'Application cache unavailable.');