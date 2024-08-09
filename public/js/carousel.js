// Fichier est appelé par le fichier templates/template.php
const carrousel = document.querySelectorAll('.carroussel');

carrousel.forEach(container => {

  const nextButton = container.querySelector('.arrow-right'),
    previousButton = container.querySelector('.arrow-left'),
    cardsContainer = container.querySelector('.cards-container');

  let translateValue = 0

  // Valeur de translateX en fonction de la width du 1er enfant (card)
  const widthValue = cardsContainer.children[0].offsetWidth

  // les valeurs de width dont on a besoin
  const carrouWidth = widthValue * cardsContainer.children.length;
  const containerWidth = cardsContainer.offsetWidth;

  // affichage des fleches seulement si besoin
  if (carrouWidth - containerWidth < 0) {
    nextButton.style.display = 'none'
    previousButton.style.display = 'none'
  }

  // défilement à gauche
  previousButton.addEventListener("click", () => {
    if (translateValue < 0) {
      translateValue += widthValue
      cardsContainer.style.transform = `translateX(${translateValue}px)`
    }
  })

  // défilement à droite
  nextButton.addEventListener("click", () => {
    // console.log(cardsContainer.offsetWidth - ((cardsContainer.children.length) * 200))
    if (translateValue > (containerWidth - carrouWidth)) {
      translateValue -= widthValue
      cardsContainer.style.transform = `translateX(${translateValue}px)`
    }
  });
});