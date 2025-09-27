<?php

namespace APP\plugins\generic\letterOfAcceptance;

use APP\facades\Repo;
use APP\handler\Handler;
use APP\plugins\generic\letterOfAcceptance\classes\Constants;
use APP\publication\Publication;
use Illuminate\Support\Facades\Mail;
use PKP\core\PKPRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LOAPageHandler extends Handler {

    /** @var null|Publication $publication being requested */
    public ?Publication $publication = null;

    public function __construct(public LetterOfAcceptancePlugin $plugin)
    {
        parent::__construct();   
    }

    public function get($args, PKPRequest $request)
    {

        $submissionId = $args[0];
        $this->publication = Repo::publication()->get((int) $submissionId);
        if(!$this->publication) {
            throw new NotFoundHttpException();
        }
        $primaryAuthor = $this->publication->getPrimaryAuthor();
        $affiliation = [];
        if($primaryAuthor) {
            $authorAffiliations = $primaryAuthor->getAffiliations();
            foreach($authorAffiliations as $affItem) {
				$affiliationRaw = $affItem->getLocalizedName();
				$affiliation[] = $affiliationRaw;
            }
        }
        $site = $request->getSite();
        $journal = $request->getContext();
        
        // Create letter
        // First get template
        $template = $this->plugin->getSetting($request->getContext()->getId(), Constants::SETTING_TEMPLATE)
            ?: $this->plugin->getSetting(null, Constants::SETTING_TEMPLATE);

        // Next Build up variables
        $args = [
            'currentDate' => date('d M Y'),
            'authorFullName' => $primaryAuthor ? $primaryAuthor->getFullName() : 'Unknown',
            'authorAffiliation' => implode("; ", $affiliation),
            'submissionTitle' => $this->publication->getLocalizedFullTitle(),
            'submissionId' => $this->publication->getId(),
            'journalName' => $journal->getLocalizedName(),
            'siteName' => $site->getLocalizedTitle(),
            'journalPrincipalContactName' => $journal->getContactName(),
            'journalPrincipalContactEmail' => $journal->getContactEmail(),
        ];

        // Replace variables (For some reason PKP does this in Mail, but it's fine to use)
        $template = Mail::compileParams($template, $args);

        if(@$_GET['html']) {
            echo $template;
        } else {
            // Use MPDF bundled with PKPLib to export a PDF
            $mpdf = new \Mpdf\Mpdf();
            $mpdf->WriteHTML($template);
            $mpdf->Output('LetterOfAcceptance-' . $submissionId . '.pdf', \Mpdf\Output\Destination::INLINE);
        }

        exit;
    }

    protected function canUserAccess($context, $user, $userRoles)
    {
        return true;
    }

}