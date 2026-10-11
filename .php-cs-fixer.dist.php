<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 SUNET
// SPDX-License-Identifier: AGPL-3.0-or-later

require_once __DIR__ . '/vendor/autoload.php';

use Nextcloud\CodingStandard\Config;

$config = new Config();
$config->getFinder()
	->in(__DIR__ . '/lib')
	->in(__DIR__ . '/tests')
	->append([__FILE__]);

return $config;
