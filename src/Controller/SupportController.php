<?php

namespace App\Controller;

use App\Form\TicketSupportType;
use App\Model\SupportData;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\ExpressionLanguage\Expression;

final class SupportController extends AbstractController
{
    #[Route('/support-ticket', name: 'app_support', methods: ['GET', 'POST'])]
    #[IsGranted(new Expression("is_granted('ROLE_BUREAU') or is_granted('ROLE_TRESORIER') or is_granted('ROLE_PROF')"))]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $data = new SupportData();

        // Pré-remplissage si l'utilisateur est connecté
        if ($user = $this->getUser()) {
            $data->email = $user->getUserIdentifier();
            if (method_exists($user, 'getFoyer') && $user->getFoyer()) {
                $data->nom = $user->getFoyer()->getNom();
            }
        }

        $form = $this->createForm(TicketSupportType::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Envoi de l'e-mail direct à S1 Digital
            try {
                $email = (new Email())
                    ->from('no-reply@studiodanse430.fr')
                    ->to('contact@s1digital.fr')
                    ->replyTo($data->email)
                    ->subject('[Ticket Studio Danse 430] ' . $data->sujet)
                    ->html("
                        <h2>Nouveau ticket de support</h2>
                        <p><strong>Nom :</strong> {$data->nom}</p>
                        <p><strong>E-mail :</strong> {$data->email}</p>
                        <p><strong>Sujet :</strong> {$data->sujet}</p>
                        <hr>
                        <p><strong>Message :</strong></p>
                        <p>" . nl2br(htmlspecialchars($data->message)) . "</p>
                    ");
                if ($data->fichier) {
                    $email->attach(
                        $data->fichier->getContent(),
                        $data->fichier->getClientOriginalName(),
                        $data->fichier->getClientMimeType()
                    );
                }

                $mailer->send($email);
                $this->addFlash('success', 'Votre demande a bien été transmise au support S1 Digital !');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi du message.');
            }

            return $this->redirectToRoute('app_home');
        }

        return $this->render('support/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
