<?php
/**
 * Simple Double Entry Bookkeeping V3
 *
 * @author Ashley Kitson
 * @copyright Ashley Kitson, 2018, UK
 * @license BSD-3-Clause See LICENSE.md
 */
namespace SAccounts\Transaction;

use Chippyash\Type\Number\IntType;
use Chippyash\Type\String\StringType;
use Monad\Match;
use Monad\Option;
use SAccounts\AccountsException;
use SAccounts\AccountType;
use SAccounts\Nominal;

/**
 * A Complex Journal transaction type
 */
class SplitTransaction
{
    /**
     * @var string
     */
    const ERR1 = 'Entry not found';

    /**
     * @var IntType
     */
    protected $txnId = null;

    /**
     * @var \DateTime
     */
    protected $date;

    /**
     * @var StringType
     */
    protected $note;

    /**
     * @var StringType
     */
    protected $src;

    /**
     * @var IntType
     */
    protected $ref;

    /**
     * @var Entries
     */
    protected $entries;

    /**
     * Constructor
     *
     * @param StringType $note Defaults to '' if not set
     * @param StringType $src  user defined source of transaction
     * @param IntType $ref user defined reference for transaction
     * @param \DateTime $date Defaults to today if not set
     */
    public function __construct(
        StringType $note = null,
        StringType $src = null,
        IntType $ref = null,
        \DateTime $date = null
    ) {
        Match::on(Option::create($date))
            ->Monad_Option_Some(function ($opt) {
                $this->date = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->date = new \DateTime();
            });

        Match::on(Option::create($note))
            ->Monad_Option_Some(function ($opt) {
                $this->note = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->note = null;
            });

        Match::on(Option::create($src))
            ->Monad_Option_Some(function ($opt) {
                $this->src = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->src = null;
            });

        Match::on(Option::create($ref))
            ->Monad_Option_Some(function ($opt) {
                $this->ref = $opt->value();
            })
            ->Monad_Option_None(function () {
                $this->ref = null;
            });

        $this->entries = new Entries();
    }

    /**
     * @param IntType $txnId
     * @return $this
     */
    public function setId(IntType $txnId)
    {
        $this->txnId = $txnId;
        return $this;
    }

    /**
     * @return IntType|null
     */
    public function getId()
    {
        return $this->txnId;
    }

    /**
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return StringType
     */
    public function getNote()
    {
        return is_null($this->note) ? new StringType('') : $this->note;
    }

    /**
     * @return StringType
     */
    public function getSrc()
    {
        return is_null($this->src) ? new StringType('') : $this->src;
    }

    /**
     * @return IntType
     */
    public function getRef()
    {
        return is_null($this->ref) ? new IntType(0) : $this->ref;
    }

    /**
     * Add a transaction entry
     *
     * @param Entry $entry
     *
     * @return $this
     */
    public function addEntry(Entry $entry)
    {
        $this->entries = $this->entries->addEntry($entry);

        return $this;
    }

    /**
     * Do the entries balance?
     *
     * @return bool
     */
    public function checkBalance()
    {
        return $this->entries->checkBalance();
    }

    /**
     * @return Entries
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @param Nominal $id
     *
     * @return Entry
     *
     * @throws AccountsException
     */
    public function getEntry(Nominal $id)
    {
        $entries = array_values($this->entries->filter(function(Entry $entry) use ($id) {
            return ($entry->getId()->get() === $id());
        })->toArray());

        if (count($entries) == 0) {
            throw new AccountsException(self::ERR1);
        }

        return $entries[0];
    }


    /**
     * Get amount if the account is balanced
     *
     * @return IntType
     *
     * @throw AccountsException
     */
    public function getAmount()
    {
        return Match::create(Option::create($this->entries->checkBalance(), false))
            ->Monad_Option_Some(
                function () {
                    $tot = 0;
                    foreach ($this->entries as $entry) {
                        $tot += $entry->getAmount()->get();
                    }
                    return new IntType($tot / 2);
                })
            ->Monad_Option_None(function () {
                throw new AccountsException('No amount for unbalanced transaction');
            })
            ->value();
    }

    /**
     * Return debit account ids
     * return zero, one or more Nominals in an array
     *
     * @return array [Nominal]
     */
    public function getDrAc()
    {
        $acs = [];
        foreach ($this->getEntries() as $entry) {
            if ($entry->getType()->getValue() == AccountType::DR) {
                $acs[] = $entry->getId();
            }
        }

        return $acs;
    }

    /**
     * Return credit account ids
     * return zero, one or more Nominals in an array
     *
     * @return array [Nominal]
     */
    public function getCrAc()
    {
        $acs = [];
        foreach ($this->getEntries() as $entry) {
            if ($entry->getType()->getValue() == AccountType::CR) {
                $acs[] = $entry->getId();
            }
        }

        return $acs;
    }

    /**
     * Is this a simple transaction, i.e. 1 dr and 1 cr entry
     *
     * @return bool
     */
    public function isSimple()
    {
        return (count($this->getDrAc()) == 1
            && count($this->getCrAc()) == 1);
    }

}