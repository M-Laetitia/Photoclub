const acteurCards = document.querySelectorAll('.acteurCard')

acteurCards.forEach(acteurCard => {
  const editButton = acteurCard.querySelector('.editButton');
  const figure = acteurCard.querySelector('.acteurFigure');
  const castingRole = acteurCard.querySelector('.castingRole');
  const role = castingRole.querySelector('.subtitle');

  editButton.addEventListener('click', () => {

    console.log(figure)
    console.log(editButton)
    console.log(role)
  })

})