function steuere(schiff, sensoren) {
    if (schiff.gelandet) {
        schiff.tanken();
        schiff.starten();
        return;
    }

    // Ohne schub() und drehen() bleibt die Geschwindigkeit unveraendert.
    if (schiff.treibstoff > 20 && schiff.geschwindigkeit < 5) {
        schiff.schub(0.3);
    }
}
