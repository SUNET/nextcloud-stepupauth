<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023-2024 Micke Nordin <kano@sunet.se>
 *
 * @license GNU AGPL version 3 or any later version
 * @author Micke Nordin <kano@sunet.se>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\StepUpAuth\Listeners;

use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use OCP\IUser;
use OCP\ISession;
use OCP\User\Events\UserLoggedInEvent;
use Psr\Log\LoggerInterface;


/**
 * Class StepUpAuth
 *
 * @package OCA\StepUpAuth\Events
 */
class UserLoggedIn implements IEventListener
{
  public function __construct(
    private ISession $session,
    private LoggerInterface $logger,
    private IAppConfig $config
  ) {}


  /**
   * @param Event $event
   */
  public function handle(Event $event): void
  {
    if (!$event instanceof UserLoggedInEvent) {
      return;
    }
    /**
     * @var IUser $user
     */
    $user = $event->getUser();
    $userbackend = $user->getBackend();
    /**
     * @var IUserBackend $userbackend
     */
    if ($userbackend->getBackendName() == 'user_saml') {
      $userData = $this->session->get('user_saml.samlUserData');
      if (!empty($userData)) {
        $formatted = $userbackend->getUserData();
        $mfaVerified = $formatted['formatted']['mfaVerified'];
        if ($mfaVerified == '1') {
          return;
        }
      }
    }
    $this->logger->debug('StepUpAuth running', ['app' => 'stepupauth']);
    $this->session->set('two_factor_auth_uid', $user->getUID());
    $this->session->set('two_factor_remember_login', true);
    $this->logger->debug('StepUpAuth finished', ['app' => 'stepupauth']);
  }
}
