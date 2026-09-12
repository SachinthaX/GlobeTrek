<?php

/*
 * GlobeTrek Adventures
 * Public Home Page
 */

$pageTitle = 'Home - GlobeTrek Adventures';

require_once __DIR__ . '/includes/header.php';

?>

<section class="hero">

    <p class="eyebrow">Travel deeper. Feel more.</p>

    <h1>
        Discover Sri Lanka with GlobeTrek Adventures
    </h1>

    <p>
        Explore unforgettable destinations, carefully planned
        travel packages, comfortable accommodation and reliable
        transportation — all in one place.
    </p>

    <a
        href="/globetrek/customer/packages.php"
        class="button"
    >
        Explore Travel Packages
    </a>

    <a
        href="/globetrek/customer/contact.php"
        class="button button-secondary hero-secondary-action"
    >
        Talk to Our Team
    </a>

</section>


<section class="content-section">

    <div class="section-heading">

        <p class="eyebrow">The GlobeTrek way</p>

        <h2>
            Travel with more intention
        </h2>

        <p>
            GlobeTrek Adventures helps travelers discover some of
            Sri Lanka's most memorable destinations through
            carefully organised travel experiences.
        </p>

    </div>


    <div class="feature-grid">

        <article class="feature-card">

            <h3>
                Explore Destinations
            </h3>

            <p>
                Discover coastal escapes, cultural cities,
                mountain adventures and heritage destinations.
            </p>

        </article>


        <article class="feature-card">

            <h3>
                Flexible Travel
            </h3>

            <p>
                Customize your trip with travel dates, traveler
                numbers and special arrangements.
            </p>

        </article>


        <article class="feature-card">

            <h3>
                Easy Online Booking
            </h3>

            <p>
                Browse packages, make a booking and manage your
                travel plans online.
            </p>

        </article>


        <article class="feature-card">

            <h3>
                Customer Support
            </h3>

            <p>
                Have a question? Submit a query to our travel
                management team.
            </p>

        </article>

    </div>

</section>


<section class="home-cta content-section">

    <div class="section-heading">

        <h2>
            Ready to Explore?
        </h2>

        <p>
            Browse the available GlobeTrek packages and find
            an experience that suits your next journey.
        </p>

    </div>


    <a
        href="/globetrek/customer/packages.php"
        class="button"
    >
        View All Packages
    </a>

</section>


<?php

require_once __DIR__ . '/includes/footer.php';

?>