<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Micke Nordin <kano@sunet.se>
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

use OC\Authentication\TwoFactorAuth\Manager;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IUser;
use OCP\User\Events\PostLoginEvent;
use Psr\Log\LoggerInterface;


/**
 * Class StepUpAuth
 *
 * @package OCA\StepUpAuth\Events
 */
class UserLoggingIn implements IEventListener
{
  public function __construct(
    private Manager $twoFactorManager,
    private LoggerInterface $logger
  ) {
  }


  /**
   * @param Event $event
   */
  public function handle(Event $event): void
  {
    if (!$event instanceof PostLoginEvent) {
      return;
    }
    /**
     * @var IUser $user
     */
    $user = $event->getUser();
    $this->logger->debug('StepUpAuth UserLoggin listener called', ['app' => 'stepupauth']);
    $backend = $user->getBackend()->getBackendName();
    if($backend != 'Database') {
      $this->logger->debug('StepUpAuth running', ['app' => 'stepupauth']);
      $this->twoFactorManager->prepareTwoFactorLogin($user, false);
      $this->logger->debug('StepUpAuth finished', ['app' => 'stepupauth']);
    }
  }
}
