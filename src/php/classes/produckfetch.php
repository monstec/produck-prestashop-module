<?php
/**
 * NOTICE OF LICENSE
 *
 * Licensed under the MonsTec Prestashop Module License v.1.0
 *
 * With the purchase or the installation of the software in your application
 * you accept the license agreement.
 *
 * You must not modify, adapt or create derivative works of this source code
 *
 * @author    Monstec UG (haftungsbeschränkt)
 * @copyright 2019 Monstec UG (haftungsbeschränkt)
 * @license   LICENSE.txt
 */

 /**
 * Fetch data from ProDuck.de backend services
 */
class ProduckFetch
{
    // endpoint url to fetch quacks overview
    private $urlQuacksEndpoint;
    // endpoint url to fetch single quack
    private $urlQuackEndpoint;
    // context for accessing endpoints
    private $streamContext;

    public function __construct($languageCode = 'en')
    {
        $this->streamContext = array(
            "http" => array(
                "method" => "GET",
                "ignore_errors" => true,
                "timeout" => (float) 30.0,
            ),
        );

        // directives of the build preprocessing done by gulp-preprocess
        // @if ENV='production'
        $this->urlQuacksEndpoint = "https://api.produck.de/chat-service/quacks?quackToken="
            . Configuration::get('PRODUCK_QUACK_TOKEN') . "&lang=" . $languageCode;
        $this->urlQuackEndpoint = "https://api.produck.de/chat-service/quack?quackToken="
            . Configuration::get('PRODUCK_QUACK_TOKEN') . "&lang=" . $languageCode . "&quackId=";
        // @endif
        // @if ENV!='production'
        // dockerhost is resolved as the "localhost", the hostmachine where everything is run at
        $this->urlQuacksEndpoint = "https://dockerhost:8443/chat-service/quacks?quackToken="
            . Configuration::get('PRODUCK_QUACK_TOKEN') . "&lang=" . $languageCode;
        $this->urlQuackEndpoint = "https://dockerhost:8443/chat-service/quack?quackToken="
            . Configuration::get('PRODUCK_QUACK_TOKEN') . "&lang=" . $languageCode . "&quackId=";
        //$this->urlQuacksEndpoint = "https://localhost:8443/chat-service/quacks?quackToken=" . Configuration::get('PRODUCK_QUACK_TOKEN');
        //$this->urlQuackEndpoint = "https://localhost:8443/chat-service/quack?quackToken=" . Configuration::get('PRODUCK_QUACK_TOKEN') . "&quackId=";

        // in dev environment, ignore the self-signed cert warnings
        $this->streamContext["ssl"] = array(
            "allow_self_signed" => true,
            "verify_peer" => false,
            "verify_peer_name" => false,
        );
        // @endif
    }

    public function getQuacks()
    {
        // @if ENV!='production'
        // for offline development return hardcoded example response
        //return '[{"quackId":2,"title":"Welche Vorteile bringt mir Smart Home aus Ihrer Sicht?","timestamp":"2017-09-04T14:23:35","views":104,"quackity":6.89,"tags":["Komfort","Sicherheit","Energieverbrauch"]},{"quackId":3,"title":"Wo kann ich Smart Home einsetzen?","timestamp":"2018-09-06T11:12:14","views":45,"quackity":8.46,"tags":["Komfort","Sicherheit","Energieverbrauch"]},{"quackId":4,"title":"Ich will mein Zuhause smart machen? Wo fange ich am besten an?","timestamp":"2018-09-09T09:48:11","views":85,"quackity":6.04,"tags":["Sprachassistent","Smart Devices","Sale%"]},{"quackId":5,"title":"Kompatibilität der HomeMatic IP Zentrale","timestamp":"2018-09-13T14:11:15","views":133,"quackity":6.71,"tags":["Homematic IP","Grundausstattung"]},{"quackId":6,"title":"Können Sie mir ein Smart Home System für unter 200€ empfehlen?","timestamp":"2018-09-18T13:27:26","views":181,"quackity":7.09,"tags":["Beleuchtung","Energieverbrauch","Sale%"]},{"quackId":7,"title":"Können HomeMatic IP Geräte an einem Bosch Smart Home Controller betrieben werden?","timestamp":"2018-09-23T09:12:59","views":153,"quackity":6.27,"tags":["Homematic IP","Energieverbrauch"]}]';
        // @endif
        //! @TODO add verification of data (is >0, is json) and errorhandling (timeout)
        // https://stackoverflow.com/a/4358138/875020
        //
        return Tools::file_get_contents(
            $this->urlQuacksEndpoint,
            false,
            stream_context_create($this->streamContext)
        );
    }

    public function getQuack($quackId)
    {
        // @if ENV!='production'
        // for offline development return hardcoded example response
        //return '{"title":"Welche Vorteile bringt mir Smart Home aus Ihrer Sicht?","timestamp":"2017-09-04T14:23:35","views":308,"quackity":6.89,"tags":["Komfort","Sicherheit","Energieverbrauch"],"messages":[{"userId":11,"text":"Welche Vorteile bringt mir Smart Home aus Ihrer Sicht?"},{"userId":53,"text":"Ich sehe vier zentrale Vorteile, die Ihnen Smart Home bringt:"},{"userId":53,"text":"1. Es erhöht Ihren Komfort. Zum Beispiel können Sie mit Smart Home ihre Heizung passend zu Ihrem Leben steuern – Verlassen Sie das Haus, kühlt die Heizung herunter, kommen Sie am Abend von der Arbeit können Sie schon vorher Ihre gewünschte Temperatur einstellen; Mit der Lichtsteuerung können Sie das Licht automatisch anschalten sobald Sie den Raum betreten und abschalten, sobald Sie ihn wieder verlassen. Ein großer Bereich sind zudem die Smarten Geräte, wie Wisch- und Saugroboter oder Sprachassistenten über die Sie schnell und bequem Wissenswertes abrufen können oder gar Bestellungen tätigen können."},{"userId":53,"text":"2. Das Thema Heizung führt uns zum nächsten Smart Home Vorteil – Dem Energiesparen: Smart Home unterstützt Sie dabei Energie besser an Ihren tatsächlichen Bedarf zu koppeln. Das heißt zum Beispiel, Strom für Licht fließt nur dann, wenn Sie es benötigen; Die Heizung heizt nur dann, wenn es erforderlich ist. Sie erhalten zudem mehr Kontrolle, durch das Erstellen von Messdaten, die Ihnen wiederum dabei helfen Ihren Energiekonsum zu optimieren."},{"userId":53,"text":"3. Ein dritter Aspekt ist die Sicherheit: Smart Home kann Sie verhältnismäßig günstig dabei unterstützen Ihr Zuhause zu überwachen und bei Gefahren Alarm zu schlagen. So werden Sie bei Rauchentwicklung oder einem Wasserbruch in Ihrer Wohnung direkt umgehend informiert und können frühzeitig Maßnahmen einleiten. Bei Abwesenheit können Bewegungssensoren, Tür- und Fenstersensoren oder Kameras dafür sorgen, dass keine Unbefugten das Haus betreten oder dies zumindest nicht unbemerkt bleibt."},{"userId":53,"text":"4. Ein letzter Bereich, der aber von enormer Bedeutung ist, ist die Gesundheit: Smart Home hilft dabei die Physis von Menschen zu überwachen, sie in Ihrem täglichen Leben zu unterstützen (insb. Ältere Menschen profitieren hier – Stichwort Ambient (bzw. Active) Assisted Living) und in Notfällen schnell für Hilfe zu sorgen (z.B. via Panikbuttons, bei deren Einsatz direkt Notärzte alarmiert werden können)"}]}';
        // @endif
        //! @TODO add verification of data (is >0, is json) and errorhandling (timeout)
        return Tools::file_get_contents(
            $this->urlQuackEndpoint.$quackId,
            false,
            stream_context_create($this->streamContext)
        );
    }

    /**
     * Call URL but neclect response - used for triggering an async update
     */
    public function triggerUrl($triggerUrl)
    {
        $data = Tools::file_get_contents($triggerUrl, false, stream_context_create($this->streamContext), 5, true);
        //$data = $this->getSslPage($triggerUrl);

        if ($data === false || Tools::strlen($data) == 0) {
            return false;
        }

        return true;
    }

    // only used for debug calls
    private function getSslPage($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        echo "http: ".$httpCode."\n";
        print_r(curl_getinfo($ch));
        echo "errno: ".curl_errno($ch)."\n";
        echo "error: ".curl_error($ch)."\n";

        curl_close($ch);

        return $result;
    }
}
