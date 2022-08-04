# Amos Cms Bridge #

Cms bridge. Bridge to connect backend to frontend.

### Installation ###
1. You need to require this package and enable the module in your configuration.

add to composer requirements in composer.json
```
"/amos/cms-bridge": "dev-master as 1.0.0",
```

or run command bash:
```bash
composer require "amos/cms-bridge": "dev-master as 1.0.0"
```

add Places migrations to console modules (console/config/migrations-amos.php):
```
'@vendor/amos/cms-bridge/src/migrations'
```
Add module to your modules-amos config in backend:
        
```php
	<?php
	$config = [
		'modules' => [
			'cmsbridge' => [
                            'class' => 'amos\cmsbridge\Module',
                        ],
		],
	];
```
    
Modify in common/params-local frontendUrl

```php
	'frontendUrl' => 'https://frontend-ersaf.devel.open20.it',
```  
