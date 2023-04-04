<?php

/**
 * Disallows users to register with a disposable mail address.
 *
 * Checks mail address during registration process and will return a validation
 * error if mail provider is not on the list of allowed mail domains.
 *
 * @package mailfilter
 * @author Tobias Herbold
 * @license MIT
 */
class MailcheckerPlugin extends Gdn_Plugin
{
    /**
     * Allow updating the list of domains manually.
     *
     * @param settingsController $sender Instance of the calling class.
     * @return void.
     * @since 0.1
     * @package mailfilter
     */
    public function settingsController_mailchecker_create($sender): void
    {
        $sender->permission('Garden.Settings.Manage');
        $sender->setHighlightRoute('dashboard/settings/plugins');
        $sender->setData('Title', Gdn::translate('Mail Filter Settings'));
        $sender->setData(
            'Description',
            Gdn::translate('List Domains with an comma between.')
        );

        // Fetch new list and give feedback abut the number of providers.
        $sender->Form = new Gdn_Form();
        if ($sender->Form->authenticatedPostBack()) {
//            $saved = $this->updateList();
            $sender->informMessage(
                Gdn::translate('Save completed')
            );
        }

        $sender->render('settings', '', 'plugins/mailfilter');
    }

    /**
     * Disallow users to register with not allowed email domains.
     *
     * @param object $sender UserModel.
     * @param mixed $args EventArguments of BeforeRegister.
     * @return void.
     * @since 0.1
     * @package mailfilter
     */
    public function userModel_beforeRegister_handler($sender, $args): void
    {
        // Get mail provider from form.
        if (isset($args['RegisteringUser'])) {
            $email = $args['RegisteringUser']['Email'];
        } else {
            $email = $args['User']['Email'];
        }
        // Return if no vaild mail.
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        if (file_exists((__DIR__ . '/allowed_domains_default.php'))) {
            require(__DIR__ . '/allowed_domains_default.php');
        }


        // Try to get most recent list which is held in cache folder.
        $allowed_domains = Gdn::config(
            'mailfilter.allowed_domains',
            $allowed_domains_default ?? ''
        );
        $allowed_domains = explode(",", $allowed_domains);

        // Get lowercase domain from email.
        $domainStart = strrpos($email, '@') + 1;
        $domain = strtolower(substr($email, $domainStart));

        // Return if domain is not blacklisted.
        if (!in_array($domain, $allowed_domains)) {
            return;
        }

        // Set error message.
        $sender->Validation->addValidationResult(
            'Email',
            'Your mail domain is not allowed.'
        );
        $args['Valid'] = false;
    }

    /**
     * Update the list in the config
     * @param string $domains list of comma seperated domains
     * @return void
     * @package mailfilter
     */
    private function updateList(string $domains): void
    {
        Gdn::config()->saveToConfig(
            'mailfilter.allowed_domains',
            $domains
        );
    }
}
