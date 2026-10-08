# CBLS-69 - validate AI results against known student personas
expected = {
    6: ["weak", "weak", "weak", "weak"],
    7: ["strong", "adequate", "weak", "adequate"]
}

actual_scores = {
    6: [40, 40, 40, 40],
    7: [100, 72, 40, 72]
}

def get_band(score):
    if score >= 80:
        return "strong"
    elif score >= 60:
        return "adequate"
    else:
        return "weak"

for sid in expected:
    band_list = []
    for s in actual_scores[sid]:
        band = get_band(s)
        band_list.append(band)
    print("\nStudent:", sid)
    print("Expected:", expected[sid])
    print("Actual:  ", band_list)
    if band_list == expected[sid]:
        print("Result: PASS")
    else:
        print("Result: FAIL")
