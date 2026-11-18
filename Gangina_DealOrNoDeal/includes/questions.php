<?php
// includes/questions.php - the question bank used by the adaptive AI.
// 36 questions: 3 difficulty tiers (1 easy, 2 medium, 3 hard) x 12 each,
// spread over 4 categories. Row format:
// [id, category, difficulty, weight 1-5, question, [4 options], correct index,
//  most plausible wrong index (kept by the 50:50 lifeline), hint]

function ai_bank(): array {
    static $bank = null;
    if ($bank !== null) {
        return $bank;
    }
    $rows = [
        // ----- Tier 1 (easy) -----
        [1,  'Science',    1, 1, 'Which gas do plants absorb from the air for photosynthesis?', ['Oxygen', 'Carbon dioxide', 'Nitrogen', 'Helium'], 1, 0, 'Animals breathe this gas out.'],
        [2,  'Science',    1, 1, 'Which planet is known as the Red Planet?', ['Venus', 'Mars', 'Jupiter', 'Mercury'], 1, 0, 'It is named after the Roman god of war.'],
        [3,  'Science',    1, 2, 'At sea level, water boils at what temperature?', ['90 C', '100 C', '110 C', '120 C'], 1, 0, 'It is a round number.'],
        [4,  'Geography',  1, 1, 'What is the capital of France?', ['Lyon', 'Marseille', 'Paris', 'Nice'], 2, 0, 'The Eiffel Tower stands there.'],
        [5,  'Geography',  1, 1, 'Which is the largest ocean on Earth?', ['Atlantic', 'Indian', 'Arctic', 'Pacific'], 3, 0, 'Its name means peaceful.'],
        [6,  'Geography',  1, 2, 'Tokyo is the capital of which country?', ['China', 'South Korea', 'Japan', 'Thailand'], 2, 1, 'It is an island nation in East Asia.'],
        [7,  'History',    1, 1, 'Who was the first President of the United States?', ['Abraham Lincoln', 'George Washington', 'Thomas Jefferson', 'John Adams'], 1, 2, 'He appears on the one-dollar bill.'],
        [8,  'History',    1, 2, 'In which year did World War II end?', ['1918', '1939', '1945', '1950'], 2, 1, 'It was the mid-1940s.'],
        [9,  'History',    1, 1, 'Which ancient people built the pyramids of Giza?', ['Romans', 'Egyptians', 'Greeks', 'Aztecs'], 1, 2, 'They lived along the Nile.'],
        [10, 'Technology', 1, 1, 'What does CPU stand for?', ['Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Core Power Unit'], 0, 2, 'It is the brain of the computer.'],
        [11, 'Technology', 1, 2, 'Which language is used to style web pages?', ['HTML', 'CSS', 'SQL', 'PHP'], 1, 0, 'The S stands for Style.'],
        [12, 'Technology', 1, 1, 'What does the "www" in a web address stand for?', ['World Wide Web', 'Wide Web World', 'Web World Wide', 'World Web Wire'], 0, 1, 'Tim Berners-Lee invented it.'],
        // ----- Tier 2 (medium) -----
        [13, 'Science',    2, 3, 'What is the chemical symbol for gold?', ['Go', 'Gd', 'Au', 'Ag'], 2, 3, 'It comes from the Latin word aurum.'],
        [14, 'Science',    2, 3, 'How many bones does an adult human have?', ['186', '206', '226', '256'], 1, 2, 'A little over two hundred.'],
        [15, 'Science',    2, 4, 'Which part of the cell is the powerhouse that makes most of its energy?', ['Nucleus', 'Ribosome', 'Mitochondria', 'Golgi apparatus'], 2, 0, 'It produces most of the cell\'s ATP.'],
        [16, 'Geography',  2, 3, 'What is the longest river in Africa?', ['Congo', 'Nile', 'Niger', 'Zambezi'], 1, 0, 'It flows north into the Mediterranean.'],
        [17, 'Geography',  2, 3, 'What is the capital of Australia?', ['Sydney', 'Melbourne', 'Canberra', 'Perth'], 2, 0, 'It was purpose-built to be the capital.'],
        [18, 'Geography',  2, 4, 'Mount Kilimanjaro is in which country?', ['Kenya', 'Tanzania', 'Uganda', 'Ethiopia'], 1, 0, 'Its capital is Dodoma.'],
        [19, 'History',    2, 3, 'Who painted the Mona Lisa?', ['Michelangelo', 'Raphael', 'Leonardo da Vinci', 'Botticelli'], 2, 0, 'He was also an inventor and engineer.'],
        [20, 'History',    2, 3, 'In which year did the Berlin Wall fall?', ['1979', '1985', '1989', '1991'], 2, 3, 'It was the final year of the 1980s.'],
        [21, 'History',    2, 4, 'Which explorer reached the Americas in 1492 sailing for Spain?', ['Vasco da Gama', 'Christopher Columbus', 'Ferdinand Magellan', 'James Cook'], 1, 2, 'He is remembered every October in the USA.'],
        [22, 'Technology', 2, 3, 'Who created the Linux kernel?', ['Linus Torvalds', 'Bill Gates', 'Steve Jobs', 'Dennis Ritchie'], 0, 3, 'He is from Finland.'],
        [23, 'Technology', 2, 3, 'What does HTTP stand for?', ['HyperText Transfer Protocol', 'HyperText Transmission Process', 'High Transfer Text Protocol', 'Host Transfer Text Page'], 0, 1, 'It is how browsers fetch pages.'],
        [24, 'Technology', 2, 4, 'In PHP, which superglobal keeps data across pages on the server?', ['$_COOKIE', '$_SESSION', '$_SERVER', '$_GET'], 1, 0, 'It needs session_start().'],
        // ----- Tier 3 (hard) -----
        [25, 'Science',    3, 4, 'About how fast does light travel in a vacuum?', ['300,000 km/s', '150,000 km/s', '30,000 km/s', '3,000,000 km/s'], 0, 1, 'About three hundred thousand kilometres every second.'],
        [26, 'Science',    3, 5, 'Which element has the atomic number 26?', ['Copper', 'Iron', 'Nickel', 'Zinc'], 1, 2, 'Its symbol is Fe.'],
        [27, 'Science',    3, 4, 'Which gas is the most abundant in Earth\'s atmosphere?', ['Oxygen', 'Argon', 'Nitrogen', 'Carbon dioxide'], 2, 0, 'It makes up roughly 78 percent of the air.'],
        [28, 'Geography',  3, 5, 'Which is the deepest lake in the world?', ['Lake Superior', 'Lake Baikal', 'Lake Tanganyika', 'Lake Victoria'], 1, 2, 'It is in Siberia.'],
        [29, 'Geography',  3, 5, 'Which country has the largest land area in Africa?', ['Algeria', 'Sudan', 'DR Congo', 'Libya'], 0, 2, 'Its capital is Algiers.'],
        [30, 'Geography',  3, 4, 'Which is the smallest country in the world by area?', ['Monaco', 'Vatican City', 'San Marino', 'Liechtenstein'], 1, 0, 'It sits inside the city of Rome.'],
        [31, 'History',    3, 4, 'Which treaty formally ended World War I with Germany in 1919?', ['Treaty of Versailles', 'Treaty of Paris', 'Treaty of Utrecht', 'Treaty of Vienna'], 0, 1, 'It was signed in the Hall of Mirrors.'],
        [32, 'History',    3, 5, 'Who was the first emperor of a unified China?', ['Kublai Khan', 'Qin Shi Huang', 'Emperor Wu', 'Sun Yat-sen'], 1, 2, 'He is linked to the Terracotta Army.'],
        [33, 'History',    3, 4, 'In which year did the French Revolution begin?', ['1776', '1789', '1799', '1815'], 1, 2, 'The Bastille was stormed that year.'],
        [34, 'Technology', 3, 4, 'In which year was the first iPhone released?', ['2005', '2007', '2009', '2010'], 1, 2, 'Steve Jobs announced it in January of that year.'],
        [35, 'Technology', 3, 5, 'Which port does HTTPS use by default?', ['80', '443', '8080', '21'], 1, 0, 'It is higher than 400.'],
        [36, 'Technology', 3, 5, 'Who wrote what is often called the first computer program, for the Analytical Engine?', ['Grace Hopper', 'Ada Lovelace', 'Alan Turing', 'Margaret Hamilton'], 1, 0, 'She was Lord Byron\'s daughter.'],
    ];
    $bank = [];
    foreach ($rows as $r) {
        $bank[$r[0]] = [
            'id' => $r[0], 'cat' => $r[1], 'd' => $r[2], 'w' => $r[3],
            'q' => $r[4], 'o' => $r[5], 'a' => $r[6], 'near' => $r[7], 'hint' => $r[8],
        ];
    }
    return $bank;
}

function ai_question(int $id): array {
    return ai_bank()[$id];
}

// Review tips shown by the Weak Spot Analyzer.
function ai_tips(string $category): array {
    $tips = [
        'Science'    => ['Review chemical symbols and the main organs and cell parts.', 'Revisit physical constants such as the speed of light.'],
        'Geography'  => ['Study capitals and the longest rivers on each continent.', 'Practise locating large lakes, mountains and the smallest countries.'],
        'History'    => ['Build a timeline of major 20th-century events.', 'Review early empires, explorers and their rulers.'],
        'Technology' => ['Revisit web basics: HTTP, HTTPS ports and what CSS does.', 'Read about computing pioneers such as Ada Lovelace.'],
    ];
    return $tips[$category] ?? ['Review this category and try again.'];
}
