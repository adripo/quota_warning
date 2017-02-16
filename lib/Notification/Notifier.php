<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\QuotaWarning\Notification;


use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\CheckQuota;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var CheckQuota */
	protected $checkQuota;

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IURLGenerator */
	protected $url;

	/**
	 * @param CheckQuota $checkQuota
	 * @param IFactory $l10nFactory
	 * @param IURLGenerator $urlGenerator
	 */
	public function __construct(CheckQuota $checkQuota, IFactory $l10nFactory, IURLGenerator $urlGenerator) {
		$this->checkQuota = $checkQuota;
		$this->l10nFactory = $l10nFactory;
		$this->url = $urlGenerator;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== Application::APP_ID) {
			// Wrong app
			throw new \InvalidArgumentException();
		}

		$usage = $this->checkQuota->getRelativeQuotaUsage($notification->getUser());
		if ($usage < CheckQuota::INFO) {
			// User is not in danger zone anymore
			throw new \InvalidArgumentException();
		} else {
			// TODO use different icons depending on the warning level
			$imagePath = $this->url->imagePath(Application::APP_ID, 'app-dark.svg');
			$notification->setIcon($this->url->getAbsoluteURL($imagePath));
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

		$parameters = $notification->getSubjectParameters();
		$notification->setParsedSubject(
			$l->t('You are using more than %d%% of your storage quota', floor($parameters['usage']))
		);
		return $notification;
	}
}
