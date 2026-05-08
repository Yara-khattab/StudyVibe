const themes = {
  fruits: ["🍎","🍌","🍇","🍉","🍒","🥝","🍍","🍓"],
  animals: ["🐶","🐱","🐼","🦁","🐸","🐵","🐰","🐯"],
  emojis: ["😀","😂","😍","😎","😭","😡","😱","🤩"],
  food: ["🍕","🍔","🍟","🌭","🍩","🍪","🍫","🍿"],
  sports: ["⚽","🏀","🏈","⚾","🎾","🏐","🥊","🏓"],
  symbols: ["⭐","❤️","🔥","⚡","🌙","☀️","💎","🎯"]
};
const urlParams = new URLSearchParams(window.location.search);
let roomCode = urlParams.get('room') || urlParams.get('code'); 
if (roomCode && roomCode !== "null") {
    localStorage.setItem('savedRoomCode', roomCode);
} else {
    roomCode = localStorage.getItem('savedRoomCode');
}
let storedBreak = localStorage.getItem('breakRemaining');
let breakRemaining;
if (storedBreak && parseInt(storedBreak) > 0) {
    breakRemaining = parseInt(storedBreak);
} else {
    let durationFromUrl = parseInt(urlParams.get('duration')) || 5;
    breakRemaining = durationFromUrl * 60;
}
let firstCard = null;
let secondCard = null;
let lockBoard = false;
let gameOver = false;
let time = 60;
let countdown;
let globalInterval;
function startGlobalBreakTimer() {
    const clockDigits = document.getElementById('break-clock-digits');    
    if (globalInterval) clearInterval(globalInterval);
    globalInterval = setInterval(() => {
        if (breakRemaining <= 0) {
            clearInterval(globalInterval);
            localStorage.removeItem('breakRemaining');
            showBreakOverAlert(); 
            return;
        }       
        breakRemaining--;
        localStorage.setItem('breakRemaining', breakRemaining);
        if (clockDigits) {
            const m = Math.floor(breakRemaining / 60);
            const s = breakRemaining % 60;
            clockDigits.textContent = `${m.toString().padStart(2, '0')}:${s.toString().padStart(2, '0')}`;
        }
    }, 1000);
}
function showBreakOverAlert() {
    gameOver = true;
    clearInterval(countdown);
    const alertSection = document.querySelector('.alert_finished_game');
    const overlay = document.getElementById('overlay');
    if (alertSection) alertSection.style.display = 'flex';
    if (overlay) overlay.style.display = 'block';
}
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
  if (gameOver || lockBoard || this === firstCard) return;
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
  firstCard = null; secondCard = null; lockBoard = false;
}
function checkWin() {
  let matchedCards = document.querySelectorAll(".matched");
  if (matchedCards.length === document.querySelectorAll(".card").length) {
    clearInterval(countdown);
    gameOver = true;
    let timeTaken = 60 - time;
    let rank = timeTaken <= 20 ? "Excellent!🏆" : (timeTaken <= 40 ? "Very Good!🌟" : "Good!👍");
    setTimeout(() => {
      showPopup("🎉 You Win!", `Time Taken: ${timeTaken}s, ${rank}`);
    }, 300);
  }
}
function startTimer() {
  let timerElement = document.getElementById("timer");
  time = 60;
  if(timerElement) {
      timerElement.style.display = "block";
      timerElement.innerText = time;
  }
  if(countdown) clearInterval(countdown);
  countdown = setInterval(() => {
    time--;
    if(timerElement) timerElement.innerText = time;
    if (time <= 0) {
      clearInterval(countdown);
      gameOver = true;
      showPopup("⏰ Time's up!", "Try again!");
    }
  }, 1000);
}
function goToRoom() {
    // استرجاع الكود من localStorage اللي إحنا خزنّاه في أول السكريبت
    const savedCode = localStorage.getItem('savedRoomCode');
    localStorage.removeItem('breakRemaining'); 

    if (savedCode && savedCode !== "null" && savedCode !== "") {
        // التعديل هنا: نغير room.php لـ private_room.php
        window.location.href = `private_room.php?code=${savedCode}`;
    } else {
        window.location.href = `join.php`;
    }
}
function showPopup(title, message) {
  const overlay = document.getElementById("overlay");
  if (overlay) overlay.style.display = "block";
  const oldPopup = document.getElementById("active-popup");
  if (oldPopup) oldPopup.remove();
  let popup = document.createElement("div");
  popup.id = "active-popup";
  popup.className = "popup-container"; 
  popup.innerHTML = `
    <div class="popup">
      <h2>${title}</h2>
      <p>${message}</p>
      <button onclick="restartExperience()" style="padding: 10px; background: #657791; color: white; border: none; border-radius: 7px; cursor: pointer; margin: 5px;">Play Again</button>
      <button onclick="goToRoom()" style="padding: 10px; background: #4CAF50; color: white; border: none; border-radius: 7px; cursor: pointer; margin: 5px;">Back to Study</button>
    </div>
  `;
  document.body.appendChild(popup);
}

function restartExperience() {
    const popup = document.getElementById("active-popup");
    if (popup) popup.remove();
    if (document.getElementById("overlay")) document.getElementById("overlay").style.display = "none";    
    resetGame();     
    document.getElementById("menu").style.display = "grid";
    document.getElementById("board").innerHTML = "";
}
function resetGame() {
  firstCard = null; secondCard = null; lockBoard = false; gameOver = false;
  clearInterval(countdown);  
  const timerElement = document.getElementById("timer");
  if (timerElement) {
      timerElement.style.display = "none";
      timerElement.innerText = "";
  }
}
window.onload = () => {
    startGlobalBreakTimer();
    
    const finishBtn = document.getElementById('back-to-room-btn');
    if (finishBtn) {
        finishBtn.onclick = goToRoom;
    }
}