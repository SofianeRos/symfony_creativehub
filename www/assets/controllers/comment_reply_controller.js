import { Controller } from "@hotwired/stimulus";

export default class extends Controller{
  static targets = ['replyForm'];

  /**
   * Affiche ou masque le formulaire de réponse pour un commentaire spécifique
   * @param {Event} event - L'evenement de clic
   */
  toggleReply(event){
    event.preventDefault();
    event.stopPropagation();

    const button = event.currentTarget;
    const commentId = button.dataset.commentId;

    if(!commentId){
      console.error('[CommentReply] Comment ID not found on reply button');
      return;
    }

    // trouver le formulaire de réponse qui correspond
    const replyForm = document.getElementById(`reply-form-${commentId}`);

    if(!replyForm){
      console.error('[CommentReply] Reply form not found for comment ID:' + commentId);
      return;
    }

    //Masquer tous les autres formulaire de reponse ouverts
    this.hideAllReplyForms();

    //afficher le formulaire de reponse 
    replyForm.classList.remove('hidden');

    //scroll leger vers le formulaire pour une meilleure UX
    replyForm.scrollIntoView({behavior: 'smooth', block: 'nearest'});

    //Focus sur le textarea pour une meilleure UX
    const textarea = replyForm.querySelector('textarea[name="comment[content]"]');
    if(textarea){
      setTimeout(()=> textarea.focus(), 100);
    }

  }

  /**
   * annule la réponse et masque le formulaire
   * @param {Event} event - L'evenement clic
   */
  cancel(event){
    event.preventDefault();
    event.stopPropagation();

    const button = event.currentTarget;
    const commentId = button.dataset.commentId;

    if(!commentId){
      console.error('[CommentReply] Comment ID not found on reply button');
      return;
    }

    // trouver le formulaire de réponse qui correspond
    const replyForm = document.getElementById(`reply-form-${commentId}`);

    if(!replyForm){
      console.error('[CommentReply] Reply form not found for comment ID:' + commentId);
      return;
    }

    //reinitialisation du contenu du textarea avant de masquer
    const textarea = replyForm.querySelector('textarea[name="comment[content]"]');
    if(textarea){
      textarea.value = '';
      //reinitialise aussi l'état de validation visuelle
      textarea.classList.remove('border-red-300');
    }
    //masquer le formulaire
    replyForm.classList.add('hidden');
  }

  /**
   * masque tous les formulaires de réponse ouverts
   */
  hideAllReplyForms(){
    const allReplyForms = document.querySelectorAll('[id^="reply-form-"]')
    allReplyForms.forEach(form => {
      //reinitialiser le contenu et l'éat de validation avant de masquer
      const textarea = form.querySelector('textarea[name="comment[content]"]');
      if(textarea){
      textarea.value = '';
      //reinitialise aussi l'état de validation visuelle
      textarea.classList.remove('border-red-300');
    }
    //masquer le formulaire
    form.classList.add('hidden');
    })
  }

  /**
   * appelé lorsque le controller est connecté au DOM
   * Utile pour setup initial si necessaire
   */
  connect(){
    //s'assurer que tous les formulaires de réponse sont masqués au chargement
    this.hideAllReplyForms();
  }

  /**
   * appelé lorsque le controller est déconnecté du DOM
   * utile pour le nettoyage si necessaire
   */
  disconnect(){
    //Cleanup si necessaire
  }

}