<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 SUNET
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\StepUpAuth\Tests\Unit\Listeners;

use OCA\StepUpAuth\Listeners\UserLoggedIn;
use OCA\StepUpAuth\Tests\Fixtures\SamlBackend;
use OCP\EventDispatcher\Event;
use OCP\ISession;
use OCP\IUser;
use OCP\User\Events\UserLoggedInEvent;
use OCP\UserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class UserLoggedInTest extends TestCase {
	private ISession&MockObject $session;
	private UserLoggedIn $listener;

	protected function setUp(): void {
		$this->session = $this->createMock(ISession::class);
		$this->listener = new UserLoggedIn($this->session, new NullLogger());
	}

	public function testUnrelatedEventDoesNotChangeTheSession(): void {
		$this->session->expects(self::never())->method('get');
		$this->session->expects(self::never())->method('set');

		$this->listener->handle(new Event());
	}

	public function testMissingBackendStillTriggersStepUp(): void {
		$this->session->expects(self::never())->method('get');

		$this->assertStepUp($this->createUser(null));
	}

	public function testOtherBackendStillTriggersStepUp(): void {
		$backend = $this->createMock(SamlBackend::class);
		$backend->method('getBackendName')->willReturn('Database');
		$backend->expects(self::never())->method('getUserData');
		$this->session->expects(self::never())->method('get');

		$this->assertStepUp($this->createUser($backend));
	}

	#[DataProvider('emptySamlSessionData')]
	public function testMissingSamlSessionDataStillTriggersStepUp(mixed $sessionData): void {
		$backend = $this->createSamlBackend();
		$backend->expects(self::never())->method('getUserData');
		$this->session->expects(self::once())->method('get')
			->with('user_saml.samlUserData')->willReturn($sessionData);

		$this->assertStepUp($this->createUser($backend));
	}

	public static function emptySamlSessionData(): array {
		return [
			'absent' => [null],
			'empty' => [[]],
		];
	}

	#[DataProvider('unverifiedSamlUserData')]
	public function testOnlyExplicitVerifiedMfaCanSkipStepUp(array $userData): void {
		$backend = $this->createSamlBackend();
		$backend->expects(self::once())->method('getUserData')->willReturn($userData);
		$this->session->expects(self::once())->method('get')
			->with('user_saml.samlUserData')->willReturn(['uid' => ['alice']]);

		$this->assertStepUp($this->createUser($backend));
	}

	public static function unverifiedSamlUserData(): array {
		return [
			'missing formatted data' => [[]],
			'missing MFA mapping' => [['formatted' => []]],
			'null' => [['formatted' => ['mfaVerified' => null]]],
			'empty' => [['formatted' => ['mfaVerified' => '']]],
			'not verified' => [['formatted' => ['mfaVerified' => '0']]],
			'boolean true' => [['formatted' => ['mfaVerified' => true]]],
			'integer one' => [['formatted' => ['mfaVerified' => 1]]],
			'leading zero' => [['formatted' => ['mfaVerified' => '01']]],
			'decimal' => [['formatted' => ['mfaVerified' => '1.0']]],
		];
	}

	public function testVerifiedSamlMfaDoesNotChangeTheSession(): void {
		$backend = $this->createSamlBackend();
		$backend->expects(self::once())->method('getUserData')
			->willReturn(['formatted' => ['mfaVerified' => '1']]);
		$this->session->expects(self::once())->method('get')
			->with('user_saml.samlUserData')->willReturn(['uid' => ['alice']]);
		$this->session->expects(self::never())->method('set');

		$this->listener->handle(new UserLoggedInEvent($this->createUser($backend), 'alice', null, false));
	}

	private function createSamlBackend(): SamlBackend&MockObject {
		$backend = $this->createMock(SamlBackend::class);
		$backend->method('getBackendName')->willReturn('user_saml');
		return $backend;
	}

	private function createUser(?UserInterface $backend): IUser {
		$user = $this->createMock(IUser::class);
		$user->expects(self::once())->method('getBackend')->willReturn($backend);
		$user->method('getUID')->willReturn('alice');
		return $user;
	}

	private function assertStepUp(IUser $user): void {
		$writes = [];
		$this->session->expects(self::exactly(2))->method('set')
			->willReturnCallback(static function (string $key, mixed $value) use (&$writes): void {
				$writes[$key] = $value;
			});

		$this->listener->handle(new UserLoggedInEvent($user, 'alice', null, false));

		self::assertSame([
			'two_factor_auth_uid' => 'alice',
			'two_factor_remember_login' => true,
		], $writes);
	}
}
