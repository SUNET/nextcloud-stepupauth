<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 SUNET
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\StepUpAuth\Tests\Fixtures;

use OCP\UserInterface;

/**
 * The optional user_saml backend methods used by the listener.
 */
interface SamlBackend extends UserInterface {
	public function getBackendName(): string;

	public function getUserData(): array;
}
