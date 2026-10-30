<?php

// TODO: CORS
// TODO: CSRF
// TODO: Rate Limiting
// TODO: Antibot / Captcha
// TODO: honeypots
// TODO: Check Origin & Referer
// TODO: CSP ??

namespace App\Controller;

use App\Dto\FeedbackInputDto;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\ObjectMapper\ObjectMapperInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/feedback', name: 'app_feedback')]
final class FeedbackController extends AbstractController
{
    #[Route('/send', name: 'app_feedback_send', methods: 'POST')]
    public function index(
        MailerInterface $mailer,
        ObjectMapperInterface $mapper,
        Request $request,
        ValidatorInterface $validator,
    ): Response
    {
        $returnMessage = [ 'success' => true, 'errors' => [] ];
        $dto = $mapper->map(json_decode($request->getContent()), FeedbackInputDto::class);
        $errors = $validator->validate($dto);

        // Honeypot
        if ($dto->website !== null && $dto->website !== '') {
            return $this->json($returnMessage);
        }

        // Error catched
        if(count($errors)) {
            $formattedErrors = [];

            foreach ($errors as $error) {
                $formattedErrors[] = [
                    'field' => $error->getPropertyPath(),
                    'message' => $error->getMessage(),
                ];
            }

            $returnMessage['success'] = false;
            $returnMessage['errors'] = $formattedErrors;

            return $this->json($returnMessage, 422);
        }

        // Prepare email
        $emailTemplate = new TemplatedEmail()
            ->from(new Address($this->getParameter('app.contact_email'), 'Slowdo'))
            ->to(new Address($this->getParameter('app.contact_email')))
            ->replyTo($dto->email)
            ->subject('[Slowdo] - ' . $dto->pattern)
            ->htmlTemplate('emails/feedback.html.twig')
            ->context([
                'message' => $dto->message,
                'senderEmail' => $dto->email
            ]);

        // Send email
        $mailer->send($emailTemplate);

        return $this->json($returnMessage);
    }
}
