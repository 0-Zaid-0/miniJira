(() => {
  document.querySelectorAll(".flash").forEach((el) => {
    const close = document.createElement("button");
    close.type = "button";
    close.className = "flash-close";
    close.setAttribute("aria-label", "Dismiss");
    close.textContent = "×";
    close.addEventListener("click", () => el.remove());
    el.appendChild(close);

    window.setTimeout(() => {
      el.classList.add("is-hiding");
      window.setTimeout(() => el.remove(), 280);
    }, 4200);
  });
})();
