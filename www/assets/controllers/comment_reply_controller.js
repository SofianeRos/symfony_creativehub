import {Controller} from "@hotwired/stimulus"

export default class extends Controller {
    static targets = ["replyForm"];

    /**
     * Affuche ou masque le formulaire de réponse au commentaire specifique.
     * @param {event} event - L'événement de clic
     */
    toggleReply(event) {
        event.preventDefault(); 
        event.stopPropagation();

        const button = event.currentTarget;
        const commentId = button.dataset.commentId;

        if (!commentId) {
            console.error('[CommentReply] Comment ID not found on reply button.');
            return;
        }

        // Trouver le formulaire de réponse correspondant
        const replyForm = document.getElementById(`reply-form-${commentId}`);
        if (!replyForm) {
            console.error('[CommentReply] Reply form not found for comment ID:' + commentId);
            return;
        }

        // masquer tous les autres formulaires de reponse ouvert
        this.hideAllReplyForms();

        //afficher le formulaire de reponse
        replyForm.classList.remove('hidden');

        // scroll leger vers le formulaire pour une meilleur UX
        replyForm.scrollIntoView({behavior: "smooth", block: "nearest"});

        // focus sur le textarea pour un emeilleur UX
        const textarea = replyForm.querySelector('textarea[name="comment[content]"]');
        if (textarea) {
            setTimeout(() => textarea.focus(), 100); // petit delai pour s'assurer que le formulaire est visible avant de focus
        }
    }

    /**
     * annule la reponse et masque le formulaire 
     * @param {event} event - L'événement de clic
     */
    cancel(event) {
                event.preventDefault(); 
        event.stopPropagation();

        const button = event.currentTarget;
        const commentId = button.dataset.commentId;

        if (!commentId) {
            console.error('[CommentReply] Comment ID not found on reply button.');
            return;
        }

        // Trouver le formulaire de réponse correspondant
        const replyForm = document.getElementById(`reply-form-${commentId}`);
        if (!replyForm) {
            console.error('[CommentReply] Reply form not found for comment ID:' + commentId);
            return;
        }
        // reinitialisation du contenu du textarea avant de masquer

         const textarea = replyForm.querySelector('textarea[name="comment[content]"]');
         if (textarea) {
             textarea.value = ''; // réinitialiser le contenu
             // reinnitialise aussi l'etat de validation visuelle
                textarea.classList.remove('border-red-300');
         }

        // masquer le formulaire de réponse
        replyForm.classList.add('hidden');
    }

    /**
     * Masque tous les formulaires de réponse ouverts.
     */
        hideAllReplyForms() {
            const allReplyForms = document.querySelectorAll('[id^="reply-form-"]');
            allReplyForms.forEach(form => {
            // reinistialiser le contenu et l'etat de validation  avnt de masquer
            const textarea = form.querySelector('textarea[name="comment[content]"]');
                if (textarea) {
             textarea.value = ''; // réinitialiser le contenu
             // reinnitialise aussi l'etat de validation visuelle
            textarea.classList.remove('border-red-300');
         }

            // masquer le formulaire de réponse
       
            form.classList.add('hidden');
        });
    }

    /**
     * appelé lorsque le controller est connecte au DOM
     * utile pour setup initial si necessaire
     */
    connect() {
        // s'assurer que tous les formulaires de reponse sont masques au chargement
        this.hideAllReplyForms();
    }
    /**
     * appelé lorsque le controller est deconnecte du DOM
     * utile pour le netoyage si necessaire
     */
    disconnect() {
        //Cleanup si necessaire

    }
}