const nav = document.querySelector('nav'),
  toggleMenu = document.getElementById('toggle-menu'),
  closeButton = document.getElementById('close-button');

if (toggleMenu) {
  toggleMenu.addEventListener('click', () => {
    nav.classList.add('show-menu')
  })
}

if (closeButton) {
  closeButton.addEventListener('click', () => {
    nav.classList.remove('show-menu')
  })
}