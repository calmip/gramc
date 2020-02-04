AppBundle/Workflow

Un ensemble de classes et d'interfaces permettant d'implémenter un workflow.

1/ Qu'est-ce qu'un workflow ?
-----------------------------

Un workflow est attaché à une classe, et dérive de la classe de base Workflow
Un workflow est défini par:
   - des signaux (des entiers)
   - des états (des entiers)
   
L'arrivée d'un signal provoque, ou pas, un changement d'état de l'objet, et est accompagnée d'un code arbitraire, défini dans une classe dérivée.
   

1/ Implémentation du workflow
-----------------------------

Le workflow est implémenté dans les classes dérivant de la classe \AppBundle\Workflow\Workflow, sous forme d'un tableau de tableaux:
   - 1er niveau : les clés sont les états de l'objet
   - 2ème niveau: les clés sont les signaux qui peuvent déclencher des transitions vers un état donné
   
La méthode Workflow::addState permet de remplir ces tableaux

(à suivre !!!)

