const themes = {
  fruits: ["🍎","🍌","🍇","🍉","🍒","🥝","🍍","🍓"],
  animals: ["🐶","🐱","🐼","🦁","🐸","🐵","🐰","🐯"],
  emojis: ["😀","😂","😍","😎","😭","😡","😱","🤩"],
  food: ["🍕","🍔","🍟","🌭","🍩","🍪","🍫","🍿"],
  sports: ["⚽","🏀","🏈","⚾","🎾","🏐","🥊","🏓"],
  symbols: ["⭐","❤️","🔥","⚡","🌙","☀️","💎","🎯"]
};
let firstCard = null;
let secondCard = null;
let lockBoard = false;
let gameOver = false;
let time = 60;
let countdown;
function startGame(type) {
  document.getElementById("menu").style.display = "none";
  resetGame();
  let selected = themes[type];
  let cards = [...selected, ...selected];
  cards.sort(() => 0.5 - Math.random());
  createBoard(cards);
  startTimer();
}
function createBoard(cards) {
  const board = document.getElementById("board");
  board.innerHTML = "";
  cards.forEach((item) => {
    let card = document.createElement("div");
    card.classList.add("card");
    card.dataset.value = item;
    card.innerHTML = item;
    card.addEventListener("click", handleClick);
    board.appendChild(card);
  });
}
function handleClick() {
  if (gameOver) return;
  if (lockBoard) return;
  if (this === firstCard) return;
  this.classList.add("open");
  if (!firstCard) {
    firstCard = this;
    return;
  }
  secondCard = this;
  lockBoard = true;
  checkMatch();
}
function checkMatch() {
  if (firstCard.dataset.value === secondCard.dataset.value) {
    firstCard.classList.add("matched");
    secondCard.classList.add("matched");
    checkWin();
    resetTurn();
  } else {
    setTimeout(() => {
      firstCard.classList.remove("open");
      secondCard.classList.remove("open");
      resetTurn();
    }, 800);
  }
}
function resetTurn() {
  firstCard = null;
  secondCard = null;
  lockBoard = false;
}
function checkWin() {
  let matchedCards = document.querySelectorAll(".matched");
  if (matchedCards.length === document.querySelectorAll(".card").length) {
    clearInterval(countdown);
    gameOver = true;
    let timeTaken = 60 - time;
    let rank = "";
    if (timeTaken <= 20) {
      rank = "Excellent!🏆";
    } else if (timeTaken <= 40) {
      rank = "Very Good!🌟";
    } else {
      rank = "Good!👍";
    }
    setTimeout(() => {
      showPopup("🎉 You Win!", `Time Taken: ${60-time}s, ${rank}` );
     
    }, 300);
  }
}
function startTimer() {
  let timerElement = document.getElementById("timer");
  time = 60;
  timerElement.innerText = time;
  countdown = setInterval(() => {
    time--;
    timerElement.innerText = time;
    if (time === 0) {
      clearInterval(countdown);
      gameOver = true;
      showPopup("⏰ Time's up!", "Try again!");
    }
  }, 1000);
}
function showPopup(title, message) {
  const overlay = document.getElementById("overlay");
  if (overlay) {
    overlay.style.display = "block";
  }
  let popup = document.createElement("div");
  popup.innerHTML = `
    <div class="popup">
      <h2>${title}</h2>
      <p>${message}</p>
      <button onclick="location.reload()" style="padding: 6px; background: #657791; color: white; border: none; border-radius: 7px; cursor: pointer; font-weight: bold; margin-top:3px;">Play Again</button>
    </div>
  `;
  document.body.appendChild(popup);
}

function resetGame() {
  firstCard = null;
  secondCard = null;
  lockBoard = false;
  gameOver = false;
  clearInterval(countdown);
}